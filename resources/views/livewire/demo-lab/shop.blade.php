@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Money;
@endphp
{{-- Démo « Boutique » (UX §5.6) : application dans une « fenêtre », guide des missions à côté. --}}
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start"
     x-data="{ toasts: [], push(message) { const id = Date.now() + Math.random(); this.toasts.push({ id, message }); setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 6000) } }"
     x-on:lab-alert.window="push($event.detail.message)"
     x-on:focus.window="$wire.$refresh()">

    {{-- ================= Fenêtre de l'application ================= --}}
    <section class="min-w-0 overflow-hidden rounded-[28px] border border-line bg-white shadow-pop" aria-label="Démo Boutique">
        <div class="flex items-center gap-3 bg-kh-blue-900 px-5 py-3 text-white">
            <span class="flex gap-1.5" aria-hidden="true">
                <span class="size-3 rounded-full bg-white/30"></span><span class="size-3 rounded-full bg-white/30"></span><span class="size-3 rounded-full bg-white/30"></span>
            </span>
            <p class="flex min-w-0 items-center gap-2 text-small font-semibold"><x-kh.icon name="store" :size="16" /> <span class="truncate">Boutique Démo · Kilakodon</span></p>
            <x-kh.badge tone="neutral" class="ms-auto">Démo</x-kh.badge>
        </div>
        <p class="flex items-start gap-2 border-b border-line bg-kh-blue-50 px-5 py-2.5 text-small text-kh-blue-900">
            <x-kh.icon name="info" :size="16" class="mt-0.5 shrink-0" />
            Démo — données fictives, réinitialisées toutes les 24 h. Aucune donnée réelle n'est utilisée.
        </p>

        @if ($state === 'expired')
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon name="clock" :size="28" /></span>
                <h2 class="mt-5 text-h3 text-ink">Cet espace de démonstration a expiré</h2>
                <p class="mx-auto mt-2 max-w-md text-slate">Les espaces sont réinitialisés après 24 h. Rechargez la page pour repartir d'une boutique toute neuve.</p>
                <div class="mt-6"><x-kh.button :href="route('lab.retail')">Relancer la démo</x-kh.button></div>
            </div>
        @elseif ($state === 'limit')
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon name="circle-check" :size="28" /></span>
                <h2 class="mt-5 text-h3 text-ink">Vous avez bien exploré la démo !</h2>
                <p class="mx-auto mt-2 max-w-md text-slate">Cet espace a atteint son nombre maximal d'opérations. Parlons maintenant de votre boutique à vous.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <x-kh.button :href="route('diagnostic.start', ['secteur' => 'retail', 'entry' => 'lab:retail'])">Faire mon diagnostic gratuit</x-kh.button>
                    <x-kh.button variant="whatsapp" :href="WhatsAppLink::forDemo()" />
                </div>
            </div>
        @else
            {{-- Onglets --}}
            <nav class="flex gap-1 overflow-x-auto border-b border-line px-3 pt-3" aria-label="Rubriques de la démo">
                @foreach (\App\Livewire\DemoLab\Shop::TABS as $key => $label)
                    <a href="{{ route('lab.retail', $key === 'caisse' ? [] : ['onglet' => $key]) }}" wire:click.prevent="show('{{ $key }}')" @if ($tab === $key) aria-current="page" @endif
                        @class([
                            'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-t-input border-b-2 px-4 text-small font-semibold transition-colors',
                            'border-kh-blue-700 text-kh-blue-700' => $tab === $key,
                            'border-transparent text-slate hover:text-ink' => $tab !== $key,
                        ])>
                        <x-kh.icon :name="['caisse' => 'shopping-cart', 'stock' => 'package', 'ventes' => 'receipt', 'tableau' => 'layout-dashboard'][$key]" :size="16" />
                        {{ $label }}
                        @if ($key === 'stock' && $products->filter->isLow()->isNotEmpty())
                            <span class="rounded-full bg-kh-red-50 px-1.5 text-tag text-kh-red-600">{{ $products->filter->isLow()->count() }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="p-4 sm:p-6">
                <div aria-live="polite">
                    @if ($error)
                        <p class="mb-4 flex items-start gap-2 rounded-input bg-kh-red-50 px-4 py-3 text-small text-kh-red-600" role="alert"><x-kh.icon name="triangle-alert" :size="18" class="mt-0.5 shrink-0" />{{ $error }}</p>
                    @endif
                    @if ($notice)
                        <p class="mb-4 flex items-start gap-2 rounded-input bg-success-50 px-4 py-3 text-small text-success-600"><x-kh.icon name="circle-check" :size="18" class="mt-0.5 shrink-0" />{{ $notice }}</p>
                    @endif
                </div>

                {{-- ================= Caisse ================= --}}
                @if ($tab === 'caisse')
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                        <div class="min-w-0">
                            <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filtrer par catégorie">
                                <button type="button" wire:click="$set('category', '')" @class(['min-h-11 rounded-full px-4 text-small font-semibold', 'bg-kh-blue-700 text-white' => $category === '', 'bg-fog text-ink hover:bg-kh-blue-50' => $category !== '']) aria-pressed="{{ $category === '' ? 'true' : 'false' }}">Tout</button>
                                @foreach ($categories as $cat)
                                    <button type="button" wire:click="$set('category', @js($cat))" @class(['min-h-11 rounded-full px-4 text-small font-semibold', 'bg-kh-blue-700 text-white' => $category === $cat, 'bg-fog text-ink hover:bg-kh-blue-50' => $category !== $cat]) aria-pressed="{{ $category === $cat ? 'true' : 'false' }}">{{ $cat }}</button>
                                @endforeach
                            </div>
                            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 2xl:grid-cols-4">
                                @foreach ($products->when($category !== '', fn ($c) => $c->where('category', $category)) as $product)
                                    <li wire:key="p-{{ $product->id }}">
                                        <button type="button" wire:click="add({{ $product->id }})" @disabled($product->stock_qty < 1)
                                            class="group flex h-full min-h-28 w-full flex-col rounded-input border border-line bg-white p-3 text-start transition-transform hover:-translate-y-0.5 hover:border-kh-blue-300 focus-visible:outline-3 focus-visible:outline-kh-blue-500 disabled:opacity-50">
                                            <span class="font-semibold leading-snug text-ink">{{ $product->name }}</span>
                                            <span class="text-small text-slate">{{ $product->unit }}</span>
                                            @if ($product->isLow())<span class="mt-1 self-start rounded-full bg-kh-red-50 px-2 text-tag text-kh-red-600">Stock bas</span>@elseif ($product->isNearThreshold())<span class="mt-1 self-start rounded-full bg-warning-50 px-2 text-tag text-warning-600">Bientôt bas</span>@endif
                                            <span class="mt-auto flex items-end justify-between gap-2 pt-2">
                                                <span class="font-semibold text-kh-blue-700">{{ Money::xof($product->price_xof) }}</span>
                                                <span @class(['rounded-full px-2 text-tag', 'bg-kh-red-50 text-kh-red-600' => $product->isLow(), 'bg-mist text-slate' => ! $product->isLow()])>{{ $product->stock_qty }}</span>
                                            </span>
                                            <span class="sr-only">— ajouter au panier ({{ $product->stock_qty }} en stock{{ $product->isLow() ? ', stock bas' : '' }})</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Panier --}}
                        <aside class="self-start rounded-card bg-fog p-5 xl:sticky xl:top-24" aria-labelledby="panier-title">
                            <div class="flex items-center justify-between">
                                <h2 id="panier-title" class="flex items-center gap-2 font-semibold text-ink"><x-kh.icon name="shopping-cart" :size="18" /> Panier</h2>
                                @if ($cartLines !== [])
                                    <button type="button" wire:click="clearCart" class="inline-flex min-h-11 items-center gap-1 text-small text-slate hover:text-kh-red-600"><x-kh.icon name="trash-2" :size="16" /> Vider</button>
                                @endif
                            </div>

                            @if ($cartLines === [])
                                <p class="mt-4 text-small text-slate">Touchez un produit pour l'ajouter au panier.</p>
                            @else
                                <ul class="mt-3 divide-y divide-line">
                                    @foreach ($cartLines as $line)
                                        <li class="flex items-center gap-2 py-2.5" wire:key="c-{{ $line['product']->id }}">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-small font-semibold text-ink">{{ $line['product']->name }}</p>
                                                <p class="text-small text-slate">{{ Money::xof($line['total']) }}</p>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button type="button" wire:click="decrement({{ $line['product']->id }})" class="flex size-11 items-center justify-center rounded-full bg-white text-ink hover:bg-kh-blue-50" aria-label="Retirer une unité — {{ $line['product']->name }}"><x-kh.icon name="minus" :size="16" /></button>
                                                <span class="w-6 text-center font-semibold tabular-nums">{{ $line['qty'] }}</span>
                                                <button type="button" wire:click="add({{ $line['product']->id }})" class="flex size-11 items-center justify-center rounded-full bg-white text-ink hover:bg-kh-blue-50" aria-label="Ajouter une unité — {{ $line['product']->name }}"><x-kh.icon name="plus" :size="16" /></button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                                <p class="mt-3 flex items-baseline justify-between border-t border-line pt-3">
                                    <span class="text-small text-slate">Total</span>
                                    <span class="font-display text-h3 font-extrabold text-ink">{{ Money::xof(array_sum(array_column($cartLines, 'total'))) }}</span>
                                </p>

                                <fieldset class="mt-4">
                                    <legend class="text-small font-semibold text-ink">Paiement</legend>
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        @foreach ($payments as $method)
                                            <label @class(['flex min-h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-input border-2 p-2 text-center text-small font-semibold has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-kh-blue-500', 'border-kh-blue-700 bg-white text-kh-blue-700' => $payment === $method->value, 'border-line bg-white text-slate' => $payment !== $method->value])>
                                                <input type="radio" wire:model.live="payment" value="{{ $method->value }}" class="sr-only">
                                                <x-kh.icon :name="$method->icon()" :size="18" />{{ $method->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="mt-4">
                                    <label for="lab-customer" class="text-small font-semibold text-ink">Client {{ $payment === 'credit' ? '' : '(facultatif)' }}</label>
                                    <select id="lab-customer" wire:model.live="customerId" class="mt-1 min-h-11 w-full rounded-input border border-line bg-white px-3 text-small">
                                        <option value="">Client de passage</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-kh.button type="button" wire:click="checkout" wire:loading.attr="disabled" class="mt-5 w-full">Encaisser</x-kh.button>
                            @endif
                        </aside>
                    </div>
                @endif

                {{-- ================= Stock ================= --}}
                @if ($tab === 'stock')
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-h3 text-ink">Stock</h2>
                        <p class="text-small text-slate">Réassort : ajoute la quantité saisie. Inventaire : remplace le stock par la quantité comptée.</p>
                    </div>
                    <div class="overflow-x-auto rounded-input border border-line">
                        <table class="w-full min-w-[640px] text-small">
                            <thead class="bg-fog text-start text-tag uppercase text-slate">
                                <tr><th scope="col" class="px-4 py-3 text-start">Produit</th><th scope="col" class="px-4 py-3 text-end">Stock</th><th scope="col" class="px-4 py-3 text-end">Seuil</th><th scope="col" class="px-4 py-3 text-start">Statut</th><th scope="col" class="px-4 py-3 text-start">Mouvement</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($products as $product)
                                    <tr wire:key="s-{{ $product->id }}" @class(['bg-kh-red-50/40' => $product->isLow()])>
                                        <th scope="row" class="px-4 py-3 text-start font-semibold text-ink">{{ $product->name }} <span class="block text-small font-normal text-slate">{{ $product->sku }} · {{ $product->unit }}</span></th>
                                        <td class="px-4 py-3 text-end font-semibold tabular-nums">{{ $product->stock_qty }}</td>
                                        <td class="px-4 py-3 text-end tabular-nums text-slate">{{ $product->alert_threshold }}</td>
                                        <td class="px-4 py-3">
                                            @if ($product->isLow())
                                                <x-kh.badge tone="red" icon="triangle-alert">Stock bas</x-kh.badge>
                                            @elseif ($product->isNearThreshold())
                                                <x-kh.badge tone="warning">Bientôt bas</x-kh.badge>
                                            @else
                                                <x-kh.badge tone="success">OK</x-kh.badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <label for="qty-{{ $product->id }}" class="sr-only">Quantité pour {{ $product->name }}</label>
                                                <input id="qty-{{ $product->id }}" type="number" min="0" max="9999" inputmode="numeric" wire:model="restock.{{ $product->id }}" class="min-h-11 w-20 rounded-input border border-line px-2 text-end">
                                                <button type="button" wire:click="restockProduct({{ $product->id }})" class="min-h-11 rounded-full bg-kh-blue-700 px-3 text-small font-semibold text-white hover:bg-kh-blue-600">Réassort</button>
                                                <button type="button" wire:click="adjustProduct({{ $product->id }})" class="min-h-11 rounded-full border border-line px-3 text-small font-semibold text-ink hover:bg-fog">Inventaire</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- ================= Ventes ================= --}}
                @if ($tab === 'ventes')
                    <h2 class="mb-4 text-h3 text-ink">Dernières ventes</h2>
                    <div class="overflow-x-auto rounded-input border border-line">
                        <table class="w-full min-w-[620px] text-small">
                            <thead class="bg-fog text-tag uppercase text-slate">
                                <tr><th scope="col" class="px-4 py-3 text-start">N°</th><th scope="col" class="px-4 py-3 text-start">Date</th><th scope="col" class="px-4 py-3 text-start">Client</th><th scope="col" class="px-4 py-3 text-start">Paiement</th><th scope="col" class="px-4 py-3 text-end">Total</th><th scope="col" class="px-4 py-3"><span class="sr-only">Facture</span></th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($sales as $sale)
                                    <tr wire:key="v-{{ $sale->id }}">
                                        <th scope="row" class="px-4 py-3 text-start font-semibold text-ink">{{ $sale->number }}</th>
                                        <td class="px-4 py-3 text-slate">{{ $sale->sold_at->setTimezone('Africa/Bamako')->locale('fr')->translatedFormat('j M, H:i') }}</td>
                                        <td class="px-4 py-3 text-slate">{{ $sale->customer?->name ?? 'Client de passage' }}</td>
                                        <td class="px-4 py-3">{{ $sale->payment_method->label() }}</td>
                                        <td class="px-4 py-3 text-end font-semibold tabular-nums">{{ Money::xof($sale->total_xof) }}</td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('lab.invoice', $sale->number) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-kh-blue-700 hover:underline"><x-kh.icon name="printer" :size="16" /> Facture<span class="sr-only"> {{ $sale->number }} (nouvel onglet)</span></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- ================= Tableau de bord ================= --}}
                @if ($tab === 'tableau' && $dashboard)
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-h3 text-ink">Tableau de bord — 7 derniers jours</h2>
                        <x-kh.badge>Chiffres fictifs · Démo</x-kh.badge>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach ([
                            ['label' => 'Chiffre d’affaires', 'value' => Money::xof($dashboard['total'])],
                            ['label' => 'Ventes', 'value' => $dashboard['count']],
                            ['label' => 'Panier moyen', 'value' => Money::xof($dashboard['count'] > 0 ? intdiv($dashboard['total'], $dashboard['count']) : 0)],
                            ['label' => 'Alertes de stock', 'value' => $dashboard['low']->count()],
                        ] as $kpi)
                            <div class="rounded-input bg-fog p-4">
                                <dt class="text-tag uppercase text-slate">{{ $kpi['label'] }}</dt>
                                <dd class="mt-1 font-display text-xl font-extrabold text-ink">{{ $kpi['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                        <figure class="rounded-input border border-line p-4">
                            <figcaption class="text-small font-semibold text-ink">Ventes par jour (FCFA)</figcaption>
                            <svg viewBox="0 0 350 180" class="mt-3 h-auto w-full" role="img" aria-labelledby="lab-chart-desc">
                                <desc id="lab-chart-desc">@foreach ($dashboard['days'] as $day){{ $day['date'] }} : {{ Money::xof($day['total']) }}. @endforeach</desc>
                                <line x1="0" y1="150" x2="350" y2="150" class="stroke-line" stroke-width="1" />
                                @foreach ($dashboard['days'] as $i => $day)
                                    @php $h = (int) round(130 * $day['total'] / $dashboard['max']); @endphp
                                    <rect x="{{ 10 + $i * 48 }}" y="{{ 150 - max($h, 2) }}" width="32" height="{{ max($h, 2) }}" rx="6" @class(['fill-kh-blue-700' => $i === 6, 'fill-kh-blue-500' => $i !== 6]) />
                                    <text x="{{ 26 + $i * 48 }}" y="170" text-anchor="middle" class="fill-slate text-[11px]">{{ $day['label'] }}</text>
                                @endforeach
                            </svg>
                        </figure>
                        <div class="grid gap-6">
                            <section class="rounded-input border border-line p-4" aria-labelledby="top-title">
                                <h3 id="top-title" class="text-small font-semibold text-ink">Meilleurs produits</h3>
                                <ol class="mt-2 space-y-2 text-small">
                                    @forelse ($dashboard['top'] as $i => $row)
                                        <li class="flex items-center justify-between gap-3"><span class="min-w-0 truncate"><span class="text-slate">{{ $i + 1 }}.</span> {{ $row['name'] }}</span><span class="shrink-0 tabular-nums text-slate">{{ $row['qty'] }} vendus</span></li>
                                    @empty
                                        <li class="text-slate">Aucune vente sur la période.</li>
                                    @endforelse
                                </ol>
                            </section>
                            <section class="rounded-input border border-line p-4" aria-labelledby="alerts-title">
                                <h3 id="alerts-title" class="text-small font-semibold text-ink">Alertes de stock</h3>
                                <ul class="mt-2 space-y-2 text-small">
                                    @forelse ($dashboard['low'] as $product)
                                        <li class="flex items-center justify-between gap-3"><span class="min-w-0 truncate">{{ $product->name }}</span><x-kh.badge tone="red">{{ $product->stock_qty }} / seuil {{ $product->alert_threshold }}</x-kh.badge></li>
                                    @empty
                                        <li class="text-slate">Aucun produit sous son seuil.</li>
                                    @endforelse
                                </ul>
                            </section>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </section>

    {{-- ================= Guide des missions ================= --}}
    @if ($state !== 'expired')
        <aside class="grid gap-4 lg:sticky lg:top-24" aria-labelledby="guide-title">
            <div class="rounded-card border border-line bg-white p-5">
                <h2 id="guide-title" class="flex items-center gap-2 font-semibold text-ink"><x-kh.icon name="flask-conical" :size="18" class="text-kh-blue-700" /> Guide</h2>
                <p class="mt-1 text-small text-slate">{{ $workspace->completedCount() }} mission{{ $workspace->completedCount() > 1 ? 's' : '' }} sur {{ count($missions) }}</p>
                <ol class="mt-4 space-y-3">
                    @foreach ($missions as $mission)
                        @php $done = $workspace->hasCompleted($mission); @endphp
                        <li class="flex gap-3">
                            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full', 'bg-success-50 text-success-600' => $done, 'bg-mist text-slate' => ! $done])>
                                @if ($done)<x-kh.icon name="check" :size="16" />@else<span class="text-tag font-semibold">{{ $loop->iteration }}</span>@endif
                            </span>
                            <div>
                                <p @class(['text-small font-semibold', 'text-success-600' => $done, 'text-ink' => ! $done])>{{ $mission->label() }}<span class="sr-only">{{ $done ? ' — terminée' : ' — à faire' }}</span></p>
                                @unless ($done)<p class="text-small leading-snug text-slate">{{ $mission->hint() }}</p>@endunless
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($workspace->showsConversion())
                <div class="kh-dark relative overflow-hidden rounded-card p-6" wire:key="conversion">
                    <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
                    <div class="relative">
                        <h2 class="font-display text-h3 font-extrabold text-white">Vous voulez ça pour votre entreprise ?</h2>
                        <p class="mt-2 text-small text-kh-blue-200">Faites le diagnostic (il est déjà pré-rempli pour le commerce) ou parlez-en directement avec nous.</p>
                        <div class="mt-5 grid gap-2">
                            <x-kh.button variant="inverse" size="sm" :href="route('diagnostic.start', ['secteur' => 'retail', 'entry' => 'lab:retail'])">Faire mon diagnostic gratuit</x-kh.button>
                            <x-kh.button variant="whatsapp-inverse" size="sm" :href="WhatsAppLink::forDemo()" />
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    @endif

    {{-- Alertes de stock : toast rouge discret (UX §5.6). --}}
    <div class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6" aria-live="assertive">
        <template x-for="toast in toasts" :key="toast.id">
            <p class="kh-toast pointer-events-auto flex max-w-sm items-start gap-2 rounded-input border border-kh-red-600/30 bg-white px-4 py-3 text-small text-kh-red-600 shadow-pop">
                <x-kh.icon name="triangle-alert" :size="18" class="mt-0.5 shrink-0" /><span x-text="toast.message"></span>
            </p>
        </template>
    </div>
</div>
