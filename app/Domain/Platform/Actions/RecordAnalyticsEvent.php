<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Analytics\AudienceConsent;
use App\Domain\Platform\Analytics\EventCatalog;
use App\Domain\Platform\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Écrit un événement de mesure first-party (TRD §9, MES-01) : liste blanche, propriétés filtrées,
 * aucune IP ni coordonnée. Événements du navigateur : seulement si la mesure d'audience est acceptée
 * (Q-011). Événements serveur de parcours : agrégés, avec visitor_key seulement après accord ; jamais
 * de visitor_key sur les événements de lead ni pour un membre de l'équipe connecté.
 * La mesure ne doit jamais casser un parcours : une erreur d'écriture est journalisée puis ignorée.
 */
class RecordAnalyticsEvent
{
    public function __construct(private readonly AudienceConsent $consent) {}

    /**
     * @param  array<array-key, mixed>  $properties
     */
    public function handle(string $name, array $properties = [], bool $fromClient = false): ?AnalyticsEvent
    {
        if ($fromClient ? ! EventCatalog::allowsFromClient($name) : ! EventCatalog::exists($name)) {
            return null;
        }
        if ($fromClient && ! $this->consent->granted()) {
            return null;
        }

        $clean = EventCatalog::filter($name, $properties);
        $utm = array_filter($clean, fn (string $key): bool => str_starts_with($key, 'utm_'), ARRAY_FILTER_USE_KEY);
        $path = isset($clean['path']) ? (string) $clean['path'] : null;
        $other = array_diff_key($clean, $utm, ['path' => true]);

        try {
            return AnalyticsEvent::query()->create([
                'name' => $name,
                'visitor_key' => $this->linkable($name) ? $this->consent->visitorKey() : null,
                'path' => $path,
                'properties' => $other !== [] ? $other : null,
                'utm' => $utm !== [] ? $utm : null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Événement de mesure non enregistré', ['event' => $name, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function linkable(string $name): bool
    {
        return ! in_array($name, EventCatalog::NEVER_LINKED, true) && ! auth()->check();
    }

    /**
     * Événement serveur, sans exception possible pour l'appelant.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function server(string $name, array $properties = []): void
    {
        app(self::class)->handle($name, $properties);
    }
}
