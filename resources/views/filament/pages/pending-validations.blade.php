@php
    $groups = $this->getGroups();
    $total = array_sum(array_map(fn (array $g): int => count($g['items']), $groups));
@endphp
<x-filament-panels::page>
    @if ($groups === [])
        <x-filament::section>
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8 text-success-600" />
                <div>
                    <p class="font-semibold text-gray-950">Tout est validé.</p>
                    <p class="text-sm text-gray-500">Aucune information « [À VALIDER] » ne reste dans les contenus, les paramètres ou l'accueil.</p>
                </div>
            </div>
        </x-filament::section>
    @else
        <p class="text-sm text-gray-600">
            {{ $total }} information(s) attendent votre confirmation. Pour chacune : remplacez le passage « [À VALIDER …] » par l'information réelle,
            ou supprimez-le, puis enregistrez. Un contenu ne peut pas être publié tant qu'il en contient.
        </p>

        <div class="grid gap-4">
            @foreach ($groups as $group)
                <x-filament::section>
                    <x-slot name="heading">
                        <span class="flex flex-wrap items-center gap-2">
                            <x-filament::badge color="gray" size="sm">{{ $group['type'] }}</x-filament::badge>
                            {{ $group['element'] }}
                            <x-filament::badge :color="$group['published'] ? 'danger' : 'warning'" size="sm">{{ $group['status'] }}</x-filament::badge>
                        </span>
                    </x-slot>
                    <x-slot name="description">{{ $group['hint'] }}</x-slot>
                    @if ($group['url'])
                        <x-slot name="afterHeader">
                            <x-filament::button tag="a" :href="$group['url']" size="sm" icon="heroicon-o-pencil-square">Corriger</x-filament::button>
                        </x-slot>
                    @endif

                    <ul class="divide-y divide-gray-100">
                        @foreach ($group['items'] as $item)
                            <li class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
                                <span class="text-sm font-semibold text-gray-700">{{ $item['field_label'] }}</span>
                                <span class="text-sm text-gray-600">{{ $item['excerpt'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
