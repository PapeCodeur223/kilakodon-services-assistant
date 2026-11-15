<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Support\PendingValidation;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Str;

/**
 * Informations encore marquées « [À VALIDER] » : contenus, paramètres et textes de l'accueil.
 * Source unique de la commande `kh:content-audit` et de la page d'administration « À valider ».
 *
 * @phpstan-type PendingItem array{
 *     type: string, element: string, status: string, published: bool, field: string,
 *     field_label: string, excerpt: string, source: 'content'|'setting'|'home', record: ContentModel|null, setting: string|null
 * }
 */
class PendingValidationReport
{
    /** @var array<class-string<ContentModel>, string> */
    public const MODELS = [
        Service::class => 'Solution',
        Sector::class => 'Secteur',
        CaseStudy::class => 'Réalisation',
        Article::class => 'Article',
        Faq::class => 'FAQ',
        Page::class => 'Page',
    ];

    /** Libellés lisibles des champs éditoriaux. */
    private const FIELD_LABELS = [
        'title' => 'Titre',
        'name' => 'Nom',
        'tagline' => 'Accroche',
        'intro' => 'Introduction',
        'body' => 'Texte de la page',
        'answer' => 'Réponse',
        'question' => 'Question',
        'duration_hint' => 'Délai indicatif',
        'features' => 'Fonctionnalité',
        'benefits' => 'Bénéfice',
        'faq' => 'FAQ de la fiche',
        'limits' => 'Limites',
        'problem' => 'Ce qui vous freine',
        'audience' => 'Pour qui ?',
        'process' => 'Comment on travaille',
        'price_hint' => 'Indication de prix',
        'headline' => 'Titre',
        'pain_points' => 'Défis',
        'outcomes' => 'Ce que ça change',
        'excerpt' => 'Résumé',
    ];

    public function __construct(private readonly Settings $settings) {}

    /**
     * @return list<PendingItem>
     */
    public function items(bool $includeDrafts = true): array
    {
        $items = [];

        foreach (self::MODELS as $model => $type) {
            $query = $model::query();
            if (! $includeDrafts) {
                $query->published();
            }

            foreach ($query->get() as $content) {
                $fields = $content->only($content->contentFields());
                foreach ($content->pendingValidationFields() as $path) {
                    $items[] = [
                        'type' => $type,
                        'element' => $this->name($content),
                        'status' => $content->status->label(),
                        'published' => $content->isPublished(),
                        'field' => $path,
                        'field_label' => $this->fieldLabel($path),
                        'excerpt' => $this->excerpt(data_get($fields, $path)),
                        'source' => 'content',
                        'record' => $content,
                        'setting' => null,
                    ];
                }
            }
        }

        foreach ($this->settings->all() as $key => $value) {
            foreach (PendingValidation::find($value) as $path) {
                $items[] = [
                    'type' => 'Paramètre',
                    'element' => $key,
                    'status' => '—',
                    'published' => true,
                    'field' => $path,
                    'field_label' => 'Valeur',
                    'excerpt' => $this->excerpt($path === '(valeur)' ? $value : data_get($value, $path)),
                    'source' => 'setting',
                    'record' => null,
                    'setting' => $key,
                ];
            }
        }

        $home = HomeContent::raw();
        foreach (PendingValidation::find($home) as $path) {
            $items[] = [
                'type' => 'Accueil',
                'element' => 'Page d\'accueil',
                'status' => 'Masqué au public',
                'published' => false,
                'field' => $path,
                'field_label' => 'Texte de l\'accueil',
                'excerpt' => $this->excerpt(data_get($home, $path)),
                'source' => 'home',
                'record' => null,
                'setting' => null,
            ];
        }

        return $items;
    }

    public function count(bool $includeDrafts = true): int
    {
        return count($this->items($includeDrafts));
    }

    /**
     * « features.2.title » → « Fonctionnalité n° 3 » ; « duration_hint » → « Délai indicatif ».
     */
    public function fieldLabel(string $path): string
    {
        $parts = explode('.', $path);
        $label = self::FIELD_LABELS[$parts[0]] ?? Str::headline($parts[0]);

        if (isset($parts[1]) && ctype_digit($parts[1])) {
            $label .= ' n° '.((int) $parts[1] + 1);
        }

        return $label;
    }

    private function excerpt(mixed $value): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', is_scalar($value) ? (string) $value : ''));
        $position = mb_stripos($text, PendingValidation::MARKER);

        // Long texte (page légale) : on montre le passage autour du marqueur.
        if ($position !== false && mb_strlen($text) > 220) {
            $start = max(0, $position - 120);
            $text = ($start > 0 ? '… ' : '').mb_substr($text, $start, 220).' …';
        }

        return $text;
    }

    private function name(ContentModel $content): string
    {
        $attributes = $content->getAttributes();

        return (string) ($attributes['title'] ?? $attributes['name'] ?? $attributes['question'] ?? $attributes['slug'] ?? $content->getKey());
    }
}
