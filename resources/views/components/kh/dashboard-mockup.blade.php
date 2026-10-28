{{-- Maquette de tableau de bord (HTML/CSS, aucune image). Données FICTIVES, étiquetées « Démo » (UX §4). --}}
@php
    $bars = [42, 58, 49, 71, 64, 86, 78];
    $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
@endphp
<div {{ $attributes->class('overflow-hidden rounded-[22px] border border-white/15 bg-white text-ink shadow-[0_40px_80px_-30px_rgb(0_0_0/.65)]') }} role="img" aria-label="Exemple de tableau de bord Kilakodon, avec des données de démonstration">
    {{-- Barre de fenêtre --}}
    <div class="flex items-center gap-2 border-b border-line bg-fog px-4 py-3">
        <span class="size-2.5 rounded-full bg-kh-red-500"></span>
        <span class="size-2.5 rounded-full bg-warning-600/60"></span>
        <span class="size-2.5 rounded-full bg-success-600/60"></span>
        <span class="ms-3 flex-1 truncate rounded-full bg-white px-3 py-1 text-[0.7rem] text-slate ring-1 ring-line">app.kilakodon · Tableau de bord</span>
        <x-kh.badge class="text-[0.7rem]!">Démo</x-kh.badge>
    </div>

    <div class="grid grid-cols-[3.25rem_1fr]">
        {{-- Barre latérale --}}
        <div class="flex flex-col items-center gap-3 border-e border-line bg-fog py-4" aria-hidden="true">
            @foreach (['layout-dashboard', 'shopping-cart', 'package', 'users', 'chart-column'] as $icon)
                <span @class(['inline-flex size-8 items-center justify-center rounded-lg', 'bg-kh-blue-700 text-white' => $loop->first, 'text-slate' => ! $loop->first])>
                    <x-kh.icon :name="$icon" :size="16" />
                </span>
            @endforeach
        </div>

        <div class="space-y-3 p-4">
            {{-- Indicateurs --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-kh-blue-700 p-3 text-white">
                    <p class="text-[0.68rem] text-kh-blue-200">Chiffre d'affaires du mois</p>
                    <p class="mt-1 font-display text-lg font-extrabold tracking-tight">2 360 000 <span class="text-xs font-semibold">FCFA</span></p>
                    <p class="mt-1 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[0.65rem] font-semibold"><x-kh.icon name="trending-up" :size="12" /> +34,8 %</p>
                </div>
                <div class="rounded-xl bg-mist p-3">
                    <p class="text-[0.68rem] text-slate">Ventes aujourd'hui</p>
                    <p class="mt-1 font-display text-lg font-extrabold tracking-tight">128</p>
                    <p class="mt-1 text-[0.65rem] text-slate">3 boutiques synchronisées</p>
                </div>
            </div>

            {{-- Graphique --}}
            <div class="rounded-xl border border-line p-3">
                <div class="flex items-center justify-between">
                    <p class="text-[0.7rem] font-semibold">Ventes 7 jours</p>
                    <p class="text-[0.65rem] text-slate">en milliers de FCFA</p>
                </div>
                <div class="mt-3 flex h-24 items-end gap-2" aria-hidden="true">
                    @foreach ($bars as $i => $height)
                        <div class="flex h-full flex-1 flex-col items-center gap-1">
                            {{-- Conteneur de hauteur fixe : la hauteur en % de la barre s'y réfère. --}}
                            <div class="flex w-full flex-1 items-end">
                                <span class="kh-bar block w-full rounded-t-md {{ $i === 5 ? 'bg-kh-red-500' : 'bg-kh-blue-500/80' }}" style="height: {{ $height }}%; --b: {{ $i }}"></span>
                            </div>
                            <span class="text-[0.6rem] text-slate">{{ $days[$i] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Dernières opérations --}}
            <div class="space-y-1.5 text-[0.7rem]">
                @foreach ([['Riz 25 kg × 2', 'Espèces', '36 000'], ['Huile 5 L', 'Mobile Money', '9 500'], ['Sucre 1 kg × 4', 'Crédit client', '3 200']] as [$label, $pay, $amount])
                    <div class="flex items-center justify-between rounded-lg bg-fog px-3 py-2">
                        <span class="font-medium">{{ $label }}</span>
                        <span class="text-slate">{{ $pay }}</span>
                        <span class="font-semibold tabular-nums">{{ $amount }} F</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
