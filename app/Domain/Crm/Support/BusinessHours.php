<?php

namespace App\Domain\Crm\Support;

use Illuminate\Support\Carbon;

/**
 * Heures ouvrées écoulées entre deux instants, dans le fuseau d'affichage (Africa/Bamako).
 * Jours et horaires : config `kh.business_hours` (lundi–samedi, 8 h–18 h [À VALIDER], Q-002).
 */
final class BusinessHours
{
    public static function between(Carbon $from, Carbon $to): float
    {
        $config = (array) config('kh.business_hours');
        $tz = (string) config('kh.display_timezone');
        $days = array_map('intval', (array) ($config['days'] ?? [1, 2, 3, 4, 5, 6]));
        $start = (int) ($config['start'] ?? 8);
        $end = (int) ($config['end'] ?? 18);

        $from = $from->copy()->timezone($tz);
        $to = $to->copy()->timezone($tz);

        if ($to->lessThanOrEqualTo($from)) {
            return 0.0;
        }

        $minutes = 0;
        for ($day = $from->copy()->startOfDay(); $day->lessThanOrEqualTo($to); $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $days, true)) {
                continue;
            }

            $open = $day->copy()->setTime($start, 0);
            $close = $day->copy()->setTime($end, 0);
            $windowStart = $from->greaterThan($open) ? $from : $open;
            $windowEnd = $to->lessThan($close) ? $to : $close;

            if ($windowEnd->greaterThan($windowStart)) {
                $minutes += (int) $windowStart->diffInMinutes($windowEnd);
            }
        }

        return round($minutes / 60, 2);
    }
}
