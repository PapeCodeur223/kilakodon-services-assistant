@php
    $m = $this->getMetrics();
    $int = fn (int $n): string => number_format($n, 0, ',', ' ');
    $funnel = $m['funnel'];
    $top = max(1, ...array_map(fn (array $step): int => $step['value'], array_slice($funnel, 2)));
    $tz = config('kh.display_timezone');
@endphp
<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600">Période :</span>
            @foreach (\App\Filament\Pages\DirectionDashboard::PERIODS as $period)
                <x-filament::button size="sm" :color="$this->days === $period ? 'primary' : 'gray'" wire:click="setPeriod({{ $period }})">
                    {{ $period }} jours
                </x-filament::button>
            @endforeach
            <span class="text-sm text-gray-500">depuis le {{ $m['since']->timezone($tz)->translatedFormat('j F Y') }}</span>
        </div>
        <p class="mt-2 text-sm text-gray-500">Chiffres recalculés à chaque affichage depuis les diagnostics, briefs, leads et la mesure first-party. Pages vues et visiteurs : seulement les visiteurs qui ont accepté la mesure d'audience. Les leads incluent tous les canaux (diagnostic, contact, rendez-vous, assistant, Démo Lab).</p>
    </x-filament::section>

    <x-filament::section heading="Entonnoir">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (array_slice($funnel, 0, 2) as $step)
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">{{ $step['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $int($step['value']) }}</p>
                </div>
            @endforeach
        </div>
        <ol class="mt-6 space-y-3">
            @foreach (array_slice($funnel, 2) as $i => $step)
                @php
                    // Taux seulement au sein d'une même chaîne (diagnostic → brief ; leads → qualifiés → gagnés).
                    $prev = $i > 0 ? $funnel[$i + 1] : null;
                    $rate = $prev !== null && $prev['chain'] === $step['chain'] && $prev['value'] > 0 ? round($step['value'] / $prev['value'] * 100) : null;
                @endphp
                <li>
                    <div class="flex items-baseline justify-between gap-4 text-sm">
                        <span class="font-medium">{{ $step['label'] }}</span>
                        <span><strong>{{ $int($step['value']) }}</strong>@if ($rate !== null) <span class="text-gray-500">· {{ $rate }} % de l'étape précédente</span>@endif</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-gray-100" aria-hidden="true">
                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ max(1, round($step['value'] / $top * 100)) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section heading="Prise en charge">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-gray-500">Délai médian</dt>
                <dd class="font-semibold">{{ $m['response']['median_hours'] !== null ? number_format($m['response']['median_hours'], 1, ',', ' ').' h' : 'Pas encore de donnée' }}</dd>
                <dt class="text-gray-500">Leads pris en charge</dt><dd class="font-semibold">{{ $int($m['response']['answered']) }}</dd>
                <dt class="text-gray-500">En attente de réponse</dt><dd class="font-semibold">{{ $int($m['response']['waiting']) }}</dd>
            </dl>
            <p class="mt-3 text-xs text-gray-500">Délai entre la création du lead et sa première prise en charge (heures calendaires).</p>
        </x-filament::section>
        <x-filament::section heading="Coût IA">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-gray-500">Coût estimé</dt>
                <dd class="font-semibold">{{ $m['ai']['pricing'] ? number_format($m['ai']['cost_usd'], 4, ',', ' ').' $' : 'Tarif non configuré' }}</dd>
                <dt class="text-gray-500">Briefs devenus leads qualifiés</dt><dd class="font-semibold">{{ $int($m['ai']['qualified_briefs']) }}</dd>
                <dt class="text-gray-500">Coût par brief qualifié</dt>
                <dd class="font-semibold">{{ $m['ai']['cost_per_qualified_brief_usd'] !== null ? number_format($m['ai']['cost_per_qualified_brief_usd'], 4, ',', ' ').' $' : '—' }}</dd>
            </dl>
        </x-filament::section>
    </div>

    <x-filament::section heading="Sources des leads">
        @if ($m['sources'] === [])
            <p class="text-sm text-gray-500">Aucun lead sur la période.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500">
                            <th class="py-2 pe-4 text-start font-medium">Source</th>
                            <th class="py-2 pe-4 text-end font-medium">Leads</th>
                            <th class="py-2 pe-4 text-end font-medium">Qualifiés</th>
                            <th class="py-2 text-end font-medium">Gagnés</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($m['sources'] as $row)
                            <tr>
                                <td class="py-2 pe-4">{{ $row['source'] }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($row['leads']) }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($row['qualified']) }}</td>
                                <td class="py-2 text-end">{{ $int($row['won']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
