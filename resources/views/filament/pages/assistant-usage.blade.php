@php
    $status = $this->getStatus();
    $month = $this->getMonth();
    $days = $this->getDays();
    $money = fn (float $usd): string => $status['pricing'] ? number_format($usd, 4, ',', ' ').' $' : 'Tarif non configuré';
    $int = fn (int $n): string => number_format($n, 0, ',', ' ');
@endphp
<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::badge :color="$status['enabled'] ? 'success' : 'gray'">{{ $status['enabled'] ? 'Assistant actif' : 'Assistant coupé' }}</x-filament::badge>
            <span class="text-sm text-gray-600">Pilote : <strong>{{ $status['driver'] }}</strong>@if ($status['driver'] === 'anthropic') · modèle {{ $status['model'] }}@endif</span>
        </div>
        @if ($status['reason'])
            <p class="mt-2 text-sm text-gray-600">{{ $status['reason'] }}</p>
        @endif
        <p class="mt-2 text-sm text-gray-500">L'interrupteur et le budget se règlent dans Réglages → Paramètres → Assistant IA. Couper l'assistant n'affecte pas le reste du site.</p>
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section heading="Aujourd'hui">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-gray-500">Budget du jour</dt>
                <dd class="font-semibold">{{ $status['budget_usd'] > 0 ? number_format($status['budget_usd'], 2, ',', ' ').' $' : 'Non fixé' }}</dd>
                <dt class="text-gray-500">Dépense estimée</dt>
                <dd class="font-semibold">{{ $money($status['spent_today_usd']) }}@if ($status['ratio'] !== null) ({{ (int) floor($status['ratio'] * 100) }} %)@endif</dd>
            </dl>
        </x-filament::section>
        <x-filament::section heading="Ce mois-ci">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-gray-500">Conversations</dt><dd class="font-semibold">{{ $int($month['conversations']) }}</dd>
                <dt class="text-gray-500">Appels au modèle</dt><dd class="font-semibold">{{ $int($month['requests']) }}</dd>
                <dt class="text-gray-500">Requêtes bloquées (gardes)</dt><dd class="font-semibold">{{ $int($month['blocked']) }}</dd>
                <dt class="text-gray-500">Jetons (entrée / sortie)</dt><dd class="font-semibold">{{ $int($month['tokens_in']) }} / {{ $int($month['tokens_out']) }}</dd>
                <dt class="text-gray-500">Coût estimé</dt><dd class="font-semibold">{{ $money($month['cost_usd']) }}</dd>
            </dl>
        </x-filament::section>
    </div>

    <x-filament::section heading="31 derniers jours">
        @if ($days === [])
            <p class="text-sm text-gray-500">Aucune activité enregistrée.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-gray-500">
                            <th class="py-2 pe-4 text-start font-medium">Jour</th>
                            <th class="py-2 pe-4 text-end font-medium">Appels</th>
                            <th class="py-2 pe-4 text-end font-medium">Bloquées</th>
                            <th class="py-2 pe-4 text-end font-medium">Jetons entrée</th>
                            <th class="py-2 pe-4 text-end font-medium">Jetons sortie</th>
                            <th class="py-2 text-end font-medium">Coût estimé</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($days as $day)
                            <tr>
                                <td class="py-2 pe-4">{{ $day->date->translatedFormat('D j M Y') }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($day->requests) }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($day->blocked_requests) }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($day->tokens_in) }}</td>
                                <td class="py-2 pe-4 text-end">{{ $int($day->tokens_out) }}</td>
                                <td class="py-2 text-end">{{ $money($day->costUsd()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
