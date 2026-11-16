<?php

namespace App\Domain\Assistant\Knowledge;

use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeChunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Recherche des fragments pertinents pour une question (TRD §7.2), uniquement dans les documents
 * approuvés et non expirés :
 * 1. FULLTEXT en langage naturel ; 2. FULLTEXT booléen avec préfixes (« stock* ») ;
 * 3. même requête enrichie des synonymes métier ; 4. repli par recherche de termes (LIKE),
 * utile pour les fragments pas encore indexés (InnoDB n'indexe qu'à la validation de la transaction).
 * Au plus 6 fragments et environ 2 500 mots de contexte.
 *
 * @phpstan-type Retrieved array{id: int, document_id: int, title: string, heading: string, content: string, url: string, score: float}
 */
class Retriever
{
    /**
     * Termes de recherche : minuscules, sans accents ni ponctuation, sans mots vides.
     *
     * @return list<string>
     */
    public function terms(string $question): array
    {
        $text = Str::lower(Str::ascii($question));
        $text = (string) preg_replace('/[^a-z0-9\s-]+/', ' ', $text);
        $stopwords = array_map(fn (string $w): string => Str::lower(Str::ascii($w)), (array) config('assistant.stopwords'));

        $terms = array_filter(
            preg_split('/[\s-]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn (string $t): bool => mb_strlen($t) >= 2 && ! in_array($t, $stopwords, true),
        );

        return array_values(array_unique($terms));
    }

    /**
     * Termes enrichis des synonymes métier (config `assistant.synonyms`).
     *
     * @param  list<string>  $terms
     * @return list<string>
     */
    public function withSynonyms(array $terms, string $question): array
    {
        $normalized = ' '.Str::lower(Str::ascii($question)).' ';
        $extra = [];

        foreach ((array) config('assistant.synonyms') as $key => $synonyms) {
            $needle = Str::lower(Str::ascii((string) $key));
            if (in_array($needle, $terms, true) || str_contains($normalized, ' '.$needle.' ')) {
                foreach ((array) $synonyms as $synonym) {
                    $extra = [...$extra, ...$this->terms((string) $synonym)];
                }
            }
        }

        return array_values(array_unique([...$terms, ...$extra]));
    }

    /**
     * @return list<Retrieved>
     */
    public function search(string $question, ?int $limit = null): array
    {
        $limit ??= (int) config('assistant.retrieval.max_chunks', 6);
        $terms = $this->terms($question);

        if ($terms === []) {
            return [];
        }

        $results = $this->fullText(implode(' ', $terms), 'natural', $limit);

        if ($results->isEmpty()) {
            $results = $this->fullText(implode(' ', array_map(fn (string $t): string => $t.'*', $terms)), 'boolean', $limit);
        }

        $expanded = $this->withSynonyms($terms, $question);
        if ($results->isEmpty() && $expanded !== $terms) {
            $results = $this->fullText(implode(' ', array_map(fn (string $t): string => $t.'*', $expanded)), 'boolean', $limit);
        }

        if ($results->isEmpty()) {
            $results = $this->termSearch($expanded, $limit);
        }

        return $this->capContext($results->all());
    }

    /**
     * @return Collection<int, Retrieved>
     */
    private function fullText(string $query, string $mode, int $limit): Collection
    {
        $base = $this->searchable();
        $grammar = $base->getQuery()->getGrammar();
        $columns = $grammar->wrap('knowledge_chunks.heading').', '.$grammar->wrap('knowledge_chunks.content');
        $against = $mode === 'boolean' ? 'in boolean mode' : 'in natural language mode';

        return $base
            ->select('knowledge_chunks.*')
            ->selectRaw("match ({$columns}) against (? {$against}) as score", [$query])
            ->whereFullText(['knowledge_chunks.heading', 'knowledge_chunks.content'], $query, ['mode' => $mode])
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(fn (KnowledgeChunk $chunk): array => $this->toArray($chunk, (float) $chunk->getAttribute('score')));
    }

    /**
     * Repli : score = occurrences des termes, un terme présent dans le titre compte triple.
     *
     * @param  list<string>  $terms
     * @return Collection<int, Retrieved>
     */
    private function termSearch(array $terms, int $limit): Collection
    {
        $candidates = $this->searchable()
            ->select('knowledge_chunks.*')
            ->where(function (Builder $q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->orWhere('knowledge_chunks.content', 'like', '%'.$term.'%')
                        ->orWhere('knowledge_chunks.heading', 'like', '%'.$term.'%');
                }
            })
            ->limit(200)
            ->get();

        return $candidates
            ->map(function (KnowledgeChunk $chunk) use ($terms): array {
                $heading = Str::lower(Str::ascii($chunk->heading));
                $content = Str::lower(Str::ascii($chunk->content));
                $score = 0;
                foreach ($terms as $term) {
                    $score += substr_count($content, $term) + 3 * substr_count($heading, $term);
                }

                return $this->toArray($chunk, (float) $score);
            })
            ->filter(fn (array $r): bool => $r['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * @return Builder<KnowledgeChunk>
     */
    private function searchable(): Builder
    {
        return KnowledgeChunk::query()
            ->with('document')
            ->join('knowledge_documents', 'knowledge_documents.id', '=', 'knowledge_chunks.document_id')
            ->where('knowledge_documents.status', KnowledgeStatus::Approved->value)
            ->where(fn ($q) => $q->whereNull('knowledge_documents.expires_at')->orWhere('knowledge_documents.expires_at', '>', now()));
    }

    /**
     * @return Retrieved
     */
    private function toArray(KnowledgeChunk $chunk, float $score): array
    {
        return [
            'id' => $chunk->id,
            'document_id' => $chunk->document_id,
            'title' => $chunk->document->title,
            'heading' => $chunk->heading,
            'content' => $chunk->content,
            'url' => $chunk->url,
            'score' => $score,
        ];
    }

    /**
     * @param  list<Retrieved>  $results
     * @return list<Retrieved>
     */
    private function capContext(array $results): array
    {
        $max = (int) config('assistant.retrieval.max_context_words', 2500);
        $kept = [];
        $words = 0;

        foreach ($results as $result) {
            $count = KnowledgeChunker::words($result['content']);
            if ($kept !== [] && $words + $count > $max) {
                break;
            }
            $kept[] = $result;
            $words += $count;
        }

        return $kept;
    }
}
