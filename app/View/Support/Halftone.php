<?php

namespace App\View\Support;

/**
 * Sphère de points du logo — docs/UX_UI_DESIGN.md §6.
 * Grille 11 × 11 inscrite dans un disque ; le rayon décroît de gauche à droite
 * (r = rMax · (1 − (x / width)^1.6)) avec un léger renflement vertical au centre.
 */
class Halftone
{
    public const GRID = 11;

    public const MIN_RADIUS = 0.6;

    /** @var array<int, list<array{cx: float, cy: float, r: float, d: float}>> */
    private static array $memo = [];

    /**
     * @return list<array{cx: float, cy: float, r: float, d: float}> points (d = délai normalisé 0–1)
     */
    public static function points(int $size = 240): array
    {
        return self::$memo[$size] ??= self::compute($size);
    }

    /**
     * @return list<array{cx: float, cy: float, r: float, d: float}>
     */
    private static function compute(int $size): array
    {
        $cell = $size / self::GRID;
        $center = $size / 2;
        $discRadius = $size / 2;
        $rMax = $cell * 0.46;
        $maxDistance = sqrt(2) * $size;
        $points = [];

        for ($row = 0; $row < self::GRID; $row++) {
            for ($col = 0; $col < self::GRID; $col++) {
                $cx = ($col + 0.5) * $cell;
                $cy = ($row + 0.5) * $cell;

                if (hypot($cx - $center, $cy - $center) > $discRadius - $cell * 0.25) {
                    continue;
                }

                $bulge = 1 + 0.12 * (1 - abs($cy - $center) / $center);
                $r = $rMax * (1 - ($cx / $size) ** 1.6) * $bulge;

                if ($r < self::MIN_RADIUS) {
                    continue;
                }

                $points[] = [
                    'cx' => round($cx, 2),
                    'cy' => round($cy, 2),
                    'r' => round(min($r, $cell * 0.5), 2),
                    // Distance au coin gauche (milieu du bord gauche), pour l'assemblage en cascade.
                    'd' => round(hypot($cx, $cy - $center) / $maxDistance * 2, 3),
                ];
            }
        }

        return $points;
    }
}
