<?php

namespace App\Domain\Assistant\Llm;

use Illuminate\Support\Str;

/**
 * AI_DRIVER=fake : réponses déterministes, sans réseau (tests, développement sans clé, corpus `kh:ai-eval`).
 * Pour l'assistant, il remplit l'outil `respond` selon des mots-clés, à partir des seuls documents fournis :
 * il sert à tester l'interface, les gardes et le validateur, pas à juger la qualité des réponses.
 */
final class FakeLlmClient implements LlmClient
{
    public const MODEL = 'fake';

    public function complete(LlmRequest $request): LlmResponse
    {
        $input = mb_strlen($request->system) + mb_strlen(json_encode($request->messages) ?: '');

        if ($request->purpose === 'assistant') {
            $tool = $this->assistant($request);

            return new LlmResponse('', self::MODEL, intdiv($input, 4), intdiv(mb_strlen($tool['answer']), 4), $tool);
        }

        $text = match ($request->purpose) {
            'diagnostic_summary' => $this->diagnosticSummary($request),
            default => 'Réponse de démonstration.',
        };

        return new LlmResponse($text, self::MODEL, intdiv($input, 4), intdiv(mb_strlen($text), 4));
    }

    /**
     * @return array{answer: string, grounding: string, citations: list<int>, suggested_action: string, clarifying_question: string|null, brief_draft: array<string, string|null>|null}
     */
    private function assistant(LlmRequest $request): array
    {
        $messages = $request->messages;
        $last = (string) (end($messages)['content'] ?? '');
        $q = Str::lower(Str::ascii($last));
        $documents = $this->documents($request->system);
        $has = fn (string ...$words): bool => collect($words)->contains(fn (string $w): bool => str_contains($q, $w));
        $reply = fn (string $answer, string $grounding = 'no_source', string $action = 'none', array $citations = [], ?array $brief = null, ?string $clarify = null): array => [
            'answer' => $answer, 'grounding' => $grounding, 'citations' => $citations,
            'suggested_action' => $action, 'clarifying_question' => $clarify, 'brief_draft' => $brief,
        ];

        if ($has('prompt', 'instruction', 'configuration', 'mode developpeur', 'administrateur', 'autres clients')) {
            return $reply('Je ne peux pas partager ma configuration ni des informations internes. Je peux en revanche vous présenter nos solutions ou vous orienter vers le diagnostic gratuit.', 'no_source', 'start_diagnostic');
        }
        if ($has('qui vous a', 'qui t\'a', 'votre developpeur', 'ton developpeur', 'developpe par', 'cree par', 'concu par')) {
            return $reply('Développé par Kilakodon Services.');
        }
        if ($has('votre nom', 'ton nom', 'qui etes-vous', 'qui es-tu', 'vous appelez', 't\'appelles')) {
            return $reply('Je suis Kilakodon Assistant, l\'assistant de Kilakodon Services. Comment puis-je vous aider ?');
        }
        if ($has('parler a quelqu', 'humain', 'conseiller', 'urgent', 'bloquee', 'whatsapp')) {
            return $reply('Je vous mets en relation avec un conseiller sur WhatsApp : il pourra reprendre votre demande.', 'no_source', 'handoff_whatsapp');
        }
        if ($has('rendez-vous', 'rdv', 'rencontrer')) {
            return $reply('Je peux transmettre votre demande de rendez-vous. Un membre de l\'équipe vous recontactera pour fixer le créneau.', 'no_source', 'request_appointment');
        }
        if ($has('prix', 'cout', 'combien', 'tarif', 'fourchette', 'remise', 'budget')) {
            return $reply('Le coût dépend du périmètre : modules, nombre d\'utilisateurs, sites et intégrations. Le plus simple est de faire le diagnostic gratuit, puis nous vous proposons un devis détaillé.', 'no_source', 'start_diagnostic');
        }
        if ($has('brief', 'je perds', 'boutique', 'points de vente')) {
            return $reply('Je vous propose de préparer un brief à partir de ce que vous m\'avez dit. Vous pourrez le relire et le corriger avant tout envoi.', 'partial', 'propose_brief', [], [
                'sector' => $has('boutique', 'commerce') ? 'Commerce' : null,
                'company_size' => null,
                'current_tools' => null,
                'main_problem' => $has('stock') ? 'Pertes ou écarts de stock' : null,
                'desired_outcome' => null,
                'timeline' => null,
                'summary' => Str::limit(trim($last), 590),
            ]);
        }
        if ($has('par ou commencer', 'sais pas')) {
            return $reply('Le diagnostic gratuit vous aide à y voir clair en 3 minutes : vos priorités et les modules adaptés.', 'no_source', 'start_diagnostic');
        }
        if ($documents === []) {
            return $reply('Je n\'ai pas cette information sur notre site. Un conseiller peut vous répondre directement.', 'no_source', 'handoff_whatsapp');
        }

        $first = $documents[0];

        return $reply(Str::limit($first['content'], 400), 'sourced', 'none', array_slice(array_column($documents, 'id'), 0, 2));
    }

    /**
     * Documents du bloc <documents> du prompt (identifiant et contenu).
     *
     * @return list<array{id: int, content: string}>
     */
    private function documents(string $system): array
    {
        preg_match_all('/<document id="(\d+)"[^>]*>\n(.*?)\n<\/document>/s', $system, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m): array => [
            'id' => (int) $m[1],
            'content' => trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ], $matches);
    }

    /**
     * Reformulation plausible construite à partir des seules données fournies (le DTO du diagnostic).
     */
    private function diagnosticSummary(LlmRequest $request): string
    {
        $data = json_decode($this->between($request->messages[0]['content'] ?? '', '<diagnostic>', '</diagnostic>'), true);
        $data = is_array($data) ? $data : [];

        $modules = array_column((array) ($data['recommendations'] ?? []), 'label');
        $pains = array_column((array) ($data['pains'] ?? []), 'label');

        $sentences = [
            'Votre organisation se situe aujourd\'hui au niveau « '.($data['maturity_label'] ?? 'Démarrage').' » : '.mb_strtolower((string) ($data['maturity_explanation'] ?? '')),
            $pains !== [] ? 'Vos priorités portent sur : '.mb_strtolower(implode(', ', $pains)).'.' : 'Vos priorités restent à préciser ensemble.',
            $modules !== [] ? 'Pour y répondre, nous recommandons d\'étudier en premier lieu : '.implode(', ', $modules).'.' : 'Un court échange nous permettra de préciser votre besoin avant toute recommandation.',
            'Chaque recommandation s\'appuie sur vos réponses et sera affinée lors d\'un premier échange avec notre équipe, qui vérifiera avec vous les points à clarifier, les données à reprendre et l\'ordre de mise en place le plus adapté à votre activité et à votre équipe.',
            'Ce résumé est un document de travail : il sert à préparer la discussion et ne constitue ni un devis ni un engagement.',
        ];

        return implode(' ', $sentences);
    }

    private function between(string $text, string $start, string $end): string
    {
        $from = mb_strpos($text, $start);
        $to = mb_strpos($text, $end);

        return $from === false || $to === false ? '' : mb_substr($text, $from + mb_strlen($start), $to - $from - mb_strlen($start));
    }
}
