<?php

namespace App\Domain\Brief\Support;

use App\Domain\Platform\Models\Sequence;
use Illuminate\Support\Facades\DB;

/**
 * Références `KLK-AAAA-NNNN`, séquentielles par année (BRF-03).
 *
 * L'incrément est un `UPDATE … SET last_value = last_value + 1` : il prend directement le verrou
 * exclusif de la ligne, puis on relit la valeur dans la même transaction. Aucune collision sous
 * concurrence, sans Redis ni verrou applicatif. (Un `INSERT IGNORE` suivi d'un `SELECT … FOR UPDATE`
 * provoquait des interblocages : verrou partagé puis exclusif.)
 *
 * La ligne de l'année est créée à l'avance (`prepare()`, planifié chaque jour) ; à défaut,
 * elle est créée au premier appel.
 */
class ReferenceGenerator
{
    public const SEQUENCE = 'brief';

    public function next(?int $year = null, string $sequence = self::SEQUENCE): string
    {
        $year ??= $this->currentYear();

        $value = DB::transaction(function () use ($year, $sequence): int {
            if ($this->increment($year, $sequence) === 0) {
                $this->prepare([$year], $sequence);
                $this->increment($year, $sequence);
            }

            return (int) Sequence::query()->where('name', $sequence)->where('year', $year)->value('last_value');
        });

        return self::format($year, $value);
    }

    /**
     * Crée les compteurs manquants (année en cours et suivante par défaut).
     *
     * @param  list<int>|null  $years
     */
    public function prepare(?array $years = null, string $sequence = self::SEQUENCE): void
    {
        $years ??= [$this->currentYear(), $this->currentYear() + 1];

        foreach ($years as $year) {
            Sequence::query()->insertOrIgnore([
                'name' => $sequence,
                'year' => $year,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function format(int $year, int $value): string
    {
        return sprintf('KLK-%d-%04d', $year, $value);
    }

    private function increment(int $year, string $sequence): int
    {
        return Sequence::query()
            ->where('name', $sequence)
            ->where('year', $year)
            ->update(['last_value' => DB::raw('last_value + 1'), 'updated_at' => now()]);
    }

    private function currentYear(): int
    {
        return (int) now(config('kh.display_timezone'))->year;
    }
}
