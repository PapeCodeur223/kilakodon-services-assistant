<?php

namespace App\Domain\Platform\Analytics;

/**
 * Liste blanche des événements de mesure (TRD §9, MES-01). Tout événement ou toute propriété absente
 * de cette liste est ignoré ; aucune valeur ressemblant à une coordonnée (e-mail, numéro, IP) n'est
 * gardée, quel que soit son type.
 *
 * Types : `slug` (identifiant court), `context` (zone ou type de page), `path` (chemin interne sans
 * requête), `host` (nom d'hôte, jamais une IP), `text` (texte court filtré), `int` (entier positif),
 * `enum:a,b` (valeur imposée).
 */
final class EventCatalog
{
    private const UTM = [
        'utm_source' => 'text', 'utm_medium' => 'text', 'utm_campaign' => 'text', 'utm_term' => 'text', 'utm_content' => 'text',
    ];

    /**
     * Événements que le navigateur peut envoyer à `/api/events` — seulement après accord (Q-011).
     *
     * @var array<string, array<string, string>>
     */
    public const CLIENT = [
        'page_view' => ['path' => 'path', 'referrer_host' => 'host'] + self::UTM,
        'cta_click' => ['cta' => 'enum:diagnostic,appointment,contact,lab', 'path' => 'path'],
        'whatsapp_click' => ['context' => 'context', 'path' => 'path'],
        'assistant_opened' => ['path' => 'path'],
    ];

    /**
     * Événements écrits par le serveur (Actions, contrôleurs) : jamais acceptés depuis le navigateur.
     *
     * @var array<string, array<string, string>>
     */
    public const SERVER = [
        'diagnostic_started' => ['entry' => 'slug'],
        'diagnostic_step_completed' => ['step' => 'int'],
        'diagnostic_completed' => ['template_version' => 'int', 'maturity_level' => 'int'],
        'brief_validated' => ['modules_count' => 'int'],
        'appointment_requested' => ['channel' => 'slug'],
        'contact_submitted' => [],
        'assistant_message' => [],
        'assistant_unavailable' => ['reason' => 'slug'],
        'lab_started' => [],
        'lab_action' => ['action' => 'enum:sale,restock,invoice,alert_seen'],
        'lead_created' => ['type' => 'slug', 'source' => 'slug'],
        'lead_stage_changed' => ['from' => 'slug', 'to' => 'slug'],
        'lead_won' => [],
    ];

    /** Événements qui ne portent jamais de visitor_key (pas de lien entre navigation et lead). */
    public const NEVER_LINKED = ['lead_created', 'lead_stage_changed', 'lead_won'];

    public static function allowsFromClient(string $name): bool
    {
        return array_key_exists($name, self::CLIENT);
    }

    public static function exists(string $name): bool
    {
        return array_key_exists($name, self::CLIENT) || array_key_exists($name, self::SERVER);
    }

    /**
     * Garde uniquement les propriétés autorisées et valides pour cet événement.
     *
     * @param  array<array-key, mixed>  $properties
     * @return array<string, string|int>
     */
    public static function filter(string $name, array $properties): array
    {
        $schema = self::CLIENT[$name] ?? self::SERVER[$name] ?? [];
        $clean = [];

        foreach ($schema as $key => $type) {
            if (! array_key_exists($key, $properties)) {
                continue;
            }
            $value = self::cast($type, $properties[$key]);
            if ($value !== null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private static function cast(string $type, mixed $value): string|int|null
    {
        if ($type === 'int') {
            return is_int($value) || (is_string($value) && ctype_digit($value))
                ? max(0, min(10000, (int) $value))
                : null;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $value = trim((string) $value);

        // Aucune coordonnée, quel que soit le type : e-mail ou suite de chiffres de type téléphone.
        if ($value === '' || self::looksPersonal($value)) {
            return null;
        }

        if (str_starts_with($type, 'enum:')) {
            return in_array($value, explode(',', substr($type, 5)), true) ? $value : null;
        }

        return match ($type) {
            'slug' => preg_match('/^[a-z][a-z0-9:_.-]{0,63}$/', $value) === 1 ? $value : null,
            'context' => preg_match('/^(?:[a-z][a-z-]{1,39}|(?:solution|sector):[a-z0-9-]{2,80})$/', $value) === 1 ? $value : null,
            'host' => self::host($value),
            'path' => self::path($value),
            'text' => mb_substr((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value), 0, 100),
            default => null,
        };
    }

    public static function looksPersonal(string $value): bool
    {
        return str_contains($value, '@')
            || preg_match('/\d[\d\s.\-\/]{6,}\d/', $value) === 1
            || filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private static function host(string $value): ?string
    {
        $host = strtolower($value);

        if (preg_match('/^[a-z0-9.-]{1,190}$/', $host) !== 1 || filter_var($host, FILTER_VALIDATE_IP) !== false || ! str_contains($host, '.')) {
            return null;
        }

        return $host;
    }

    /**
     * Chemin interne seulement (pas d'hôte, ni requête, ni ancre) — les jetons privés sont masqués.
     */
    public static function path(string $value): ?string
    {
        // Une URL complète (autre site, schéma) est refusée : seuls les chemins internes sont gardés.
        if (parse_url($value, PHP_URL_SCHEME) !== null || parse_url($value, PHP_URL_HOST) !== null) {
            return null;
        }

        $path = (string) parse_url($value, PHP_URL_PATH);

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        // Liens privés : le jeton ne doit jamais finir dans les statistiques.
        $path = (string) preg_replace('#^/(brief|diagnostic/reprendre)/[^/]+#', '/$1/*', $path);

        return self::looksPersonal(rawurldecode($path)) ? null : mb_substr($path, 0, 255);
    }
}
