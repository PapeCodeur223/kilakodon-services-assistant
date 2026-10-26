<?php

namespace App\Domain\Crm\Support;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Normalisation E.164, pays par défaut Mali (TRD §2).
 */
final class PhoneNormalizer
{
    public const DEFAULT_COUNTRY = 'ML';

    /**
     * @return string|null numéro E.164, ou null s'il n'est pas valide
     */
    public static function toE164(?string $raw, string $country = self::DEFAULT_COUNTRY): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        try {
            $phone = new PhoneNumber($raw, $country);

            return $phone->isValid() ? $phone->formatE164() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
