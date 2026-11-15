@php
    use App\Domain\Crm\Enums\LeadStage;
    use App\Filament\Resources\Leads\LeadResource;

    $columns = $this->getColumns();
    $tz = config('kh.display_timezone');
@endphp
<x-filament-panels::page>
    <p class="text-sm text-gray-500">
        Glissez une carte vers une autre étape. « Perdu » et « En pause » demandent un motif ; chaque changement est historisé.
        Au clavier : ouvrez la fiche du lead et utilisez « Changer d'étape ».
    </p>

    <div
        class="-mx-4 flex gap-4 overflow-x-auto px-4 pb-4"
        x-data="{ dragging: null }"
    >
        @foreach (LeadStage::cases() as $stage)
            @php $leads = $columns[$stage->value]; @endphp
            <section
                wire:key="col-{{ $stage->value }}"
                class="kh-kanban-col flex w-72 shrink-0 flex-col p-3"
                aria-label="{{ $stage->label() }}"
                x-data="{ over: false }"
                x-bind:data-over="over"
                x-on:dragover.prevent="over = true"
                x-on:dragleave="over = false"
                x-on:drop.prevent="over = false; if (dragging) { $wire.moveLead(dragging, '{{ $stage->value }}') }; dragging = null"
            >
                <header class="mb-3 flex items-center justify-between px-1">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-gray-950">
                        <x-filament::badge :color="$stage->color()" size="sm">{{ $stage->label() }}</x-filament::badge>
                    </h2>
                    <span class="text-xs font-semibold text-gray-500">{{ $leads->count() }}{{ $leads->count() >= \App\Filament\Resources\Leads\Pages\LeadsKanban::PER_COLUMN ? '+' : '' }}</span>
                </header>

                <ol class="flex min-h-24 flex-1 flex-col gap-2">
                    @forelse ($leads as $lead)
                        <li
                            wire:key="lead-{{ $lead->id }}"
                            draggable="true"
                            class="kh-kanban-card cursor-grab rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 active:cursor-grabbing"
                            x-bind:data-dragging="dragging === {{ $lead->id }}"
                            x-on:dragstart="dragging = {{ $lead->id }}; $event.dataTransfer.effectAllowed = 'move'"
                            x-on:dragend="dragging = null"
                        >
                            <a href="{{ LeadResource::getUrl('view', ['record' => $lead]) }}" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-lg">
                                <p class="truncate text-sm font-semibold text-gray-950">{{ $lead->contact->full_name }}</p>
                                @if ($lead->contact->company)
                                    <p class="truncate text-xs text-gray-500">{{ $lead->contact->company->name }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <x-filament::badge color="gray" size="sm">{{ $lead->type->label() }}</x-filament::badge>
                                    @if ($lead->fit_score)
                                        <x-filament::badge color="primary" size="sm">Adéquation {{ $lead->fit_score }}/3</x-filament::badge>
                                    @endif
                                </div>
                                <p @class([
                                    'mt-2 text-xs',
                                    'font-semibold text-danger-600' => $stage->isActive() && ($lead->next_action === null || ($lead->next_action_at?->isPast() ?? true)),
                                    'text-gray-600' => ! ($stage->isActive() && ($lead->next_action === null || ($lead->next_action_at?->isPast() ?? true))),
                                ])>
                                    {{ $lead->next_action ?? 'À traiter' }}
                                    @if ($lead->next_action_at)
                                        · {{ $lead->next_action_at->timezone($tz)->format('d/m H:i') }}
                                    @endif
                                </p>
                                <p class="mt-2 flex items-center justify-between text-[0.7rem] text-gray-400">
                                    <span>#{{ $lead->id }} · {{ $lead->created_at?->timezone($tz)->format('d/m') }}</span>
                                    <span>{{ $lead->owner?->name ?? 'Non attribué' }}</span>
                                </p>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-xs text-gray-400">Aucun lead</li>
                    @endforelse
                </ol>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
