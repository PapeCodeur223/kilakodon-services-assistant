<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Support\ContentPreview;

/**
 * Textes de l'accueil (resources/content/home.php). Les éléments contenant « [À VALIDER] »
 * sont retirés pour le public et conservés en prévisualisation équipe.
 */
final class HomeContent
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->filter(self::raw());
    }

    /**
     * @return array<string, mixed>
     */
    public static function raw(): array
    {
        return require resource_path('content/home.php');
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function filter(array $content): array
    {
        foreach ($content as $section => $blocks) {
            foreach (['items', 'cards'] as $list) {
                if (isset($blocks[$list]) && is_array($blocks[$list])) {
                    $content[$section][$list] = array_values(array_filter($blocks[$list], ContentPreview::shows(...)));
                }
            }
        }

        return $content;
    }
}
