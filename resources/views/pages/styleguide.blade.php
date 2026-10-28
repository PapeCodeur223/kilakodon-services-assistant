<x-layouts.public title="Styleguide" :noindex="true" header-tone="light">
    @php
        $colors = [
            'kh-blue-950', 'kh-blue-900', 'kh-blue-700', 'kh-blue-500', 'kh-blue-200', 'kh-blue-100', 'kh-blue-50',
            'kh-red-500', 'kh-red-600', 'kh-red-900', 'kh-red-50',
            'ink', 'slate', 'smoke', 'line', 'canvas', 'fog', 'mist',
            'success-600', 'warning-600', 'whatsapp',
        ];
        $swatch = [
            'kh-blue-950' => 'bg-kh-blue-950', 'kh-blue-900' => 'bg-kh-blue-900', 'kh-blue-700' => 'bg-kh-blue-700',
            'kh-blue-500' => 'bg-kh-blue-500', 'kh-blue-200' => 'bg-kh-blue-200', 'kh-blue-100' => 'bg-kh-blue-100',
            'kh-blue-50' => 'bg-kh-blue-50', 'kh-red-500' => 'bg-kh-red-500', 'kh-red-600' => 'bg-kh-red-600',
            'kh-red-900' => 'bg-kh-red-900', 'kh-red-50' => 'bg-kh-red-50', 'ink' => 'bg-ink', 'slate' => 'bg-slate',
            'smoke' => 'bg-smoke', 'line' => 'bg-line', 'canvas' => 'bg-canvas', 'fog' => 'bg-fog', 'mist' => 'bg-mist',
            'success-600' => 'bg-success-600', 'warning-600' => 'bg-warning-600', 'whatsapp' => 'bg-whatsapp',
        ];
        $icons = collect(glob(resource_path('svg/lucide/*.svg')))->map(fn ($f) => basename($f, '.svg'));
    @endphp

    <div class="kh-container pt-12 pb-4">
        <x-kh.eyebrow>Local uniquement · design system</x-kh.eyebrow>
        <h1 class="mt-4 text-h1">Styleguide <em>Kilakodon</em></h1>
        <p class="kh-prose mt-4 text-lead text-slate">Tous les composants <code>x-kh.*</code> et leurs états. Page servie uniquement en environnement local (middleware <code>env:local</code>), en <code>noindex</code>.</p>
        <nav aria-label="Sommaire du styleguide" class="mt-8 flex flex-wrap gap-2">
            @foreach (['couleurs' => 'Couleurs', 'typographie' => 'Typographie', 'boutons' => 'Boutons', 'liens' => 'Liens & badges', 'cartes' => 'Cartes', 'artefacts' => 'Artefacts', 'formulaires' => 'Formulaires', 'icones' => 'Icônes', 'motif' => 'Sphère de points', 'animations' => 'Animations'] as $anchor => $label)
                <a href="#{{ $anchor }}" class="rounded-full bg-mist px-3 py-1.5 text-small hover:bg-kh-blue-50">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <x-kh.section id="couleurs" title="Couleurs" lead="Tokens de UX §3.1. Le rouge reste une ponctuation (< 3 % de la surface).">
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
            @foreach ($colors as $color)
                <li>
                    <span class="block h-16 rounded-input border border-line {{ $swatch[$color] }}"></span>
                    <code class="mt-2 block text-small text-slate">{{ $color }}</code>
                </li>
            @endforeach
        </ul>
    </x-kh.section>

    <x-kh.section id="typographie" tone="fog" eyebrow="Plus Jakarta Sans · Inter" title="Typographie">
        <div class="space-y-8">
            <div><p class="mb-2 text-tag uppercase text-slate">display</p><p class="kh-hero-title font-display text-display">Pilotez votre entreprise avec des outils <em>faits pour elle</em>.</p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">h1</p><p class="font-display text-h1">Une méthode claire, <em>du premier échange au support</em></p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">h2</p><p class="font-display text-h2">Six expertises, <em>un seul interlocuteur</em></p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">h3 (Plus Jakarta Sans 700)</p><p class="text-h3 font-medium">Stocks, facturation &amp; caisse</p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">lead</p><p class="kh-prose text-lead text-slate">Du logiciel de caisse à l'ERP complet, nous construisons ce dont vous avez besoin — ni plus, ni moins.</p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">body</p><p class="kh-prose text-body">Que vous teniez une boutique ou un réseau de magasins, la différence entre une bonne et une mauvaise année se joue souvent dans le stock et la caisse.</p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">small</p><p class="text-small text-slate">3 minutes · sans inscription · résultat immédiat</p></div>
            <div><p class="mb-2 text-tag uppercase text-slate">eyebrow</p><x-kh.eyebrow>Logiciels de gestion sur mesure — Bamako</x-kh.eyebrow></div>
        </div>
    </x-kh.section>

    <x-kh.section id="boutons" title="Boutons">
        <div class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <x-kh.button :href="url('/diagnostic')">Faire mon diagnostic gratuit</x-kh.button>
                <x-kh.button variant="ghost" :href="url('/demo-lab')">Essayer le Démo Lab</x-kh.button>
                <x-kh.button variant="whatsapp" :href="\App\Domain\Platform\Support\WhatsAppLink::for('styleguide')" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-kh.button type="submit">Bouton (type submit)</x-kh.button>
                <x-kh.button disabled>Désactivé</x-kh.button>
                <x-kh.button size="sm" :arrow="false">Diagnostic</x-kh.button>
                <x-kh.button variant="ghost" size="sm">Petit ghost</x-kh.button>
            </div>
            <div class="flex flex-wrap items-center gap-3 rounded-card bg-kh-blue-950 p-6">
                <x-kh.button variant="inverse">Faire mon diagnostic gratuit</x-kh.button>
                <x-kh.button variant="whatsapp-inverse" :href="\App\Domain\Platform\Support\WhatsAppLink::for('styleguide')" />
                <x-kh.button variant="ghost-inverse">Ghost inversé</x-kh.button>
            </div>
        </div>
    </x-kh.section>

    <x-kh.section id="liens" tone="fog" title="Liens & badges">
        <div class="flex flex-wrap items-center gap-6">
            <x-kh.link-arrow :href="url('/methode')">Découvrir notre méthode</x-kh.link-arrow>
            <x-kh.link-arrow :href="url('/solutions')">Toutes les solutions</x-kh.link-arrow>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-2">
            <x-kh.badge>Démo</x-kh.badge>
            <x-kh.badge tone="blue">Interactif</x-kh.badge>
            <x-kh.badge tone="red" icon="circle-alert">Stock critique</x-kh.badge>
            <x-kh.badge tone="success" icon="circle-check">Validé</x-kh.badge>
            <x-kh.badge tone="warning" icon="triangle-alert">À valider</x-kh.badge>
        </div>
    </x-kh.section>

    <x-kh.section id="cartes" title="Cartes" lead="Cartes neutres sans ombre au repos ; survol (si lien) : -4 px, ombre douce, fond blanc.">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'layout-dashboard', 'title' => 'ERP & CRM sur mesure', 'tagline' => 'Toute votre entreprise dans un seul outil : ventes, clients, équipes, finances.'],
                ['icon' => 'package', 'title' => 'Stocks, facturation & caisse', 'tagline' => 'Sachez exactement ce que vous avez, ce que vous vendez et ce que vous encaissez.'],
                ['icon' => 'app-window', 'title' => 'Applications web métiers', 'tagline' => 'L\'outil exact dont votre métier a besoin, accessible depuis n\'importe quel navigateur.'],
            ] as $i => $item)
                <x-kh.card :href="route('solutions.index')" :icon="$item['icon']" :title="$item['title']" data-reveal style="--i: {{ $i }}">
                    {{ $item['tagline'] }}
                    <x-slot:footer><span class="font-medium text-ink">En savoir plus <span aria-hidden="true">→</span></span></x-slot:footer>
                </x-kh.card>
            @endforeach
            <x-kh.card title="Carte statique">Sans lien : aucun effet de survol.</x-kh.card>
            <x-kh.card-accent tone="blue" title="Ce que ce n'est pas">Nous ne lançons pas de développement sans cahier des charges validé : c'est ce qui protège votre budget.</x-kh.card-accent>
            <x-kh.card-accent tone="red" title="Un logiciel n'est utile que s'il est *utilisé*.">Nous concevons des outils simples pour vos équipes, nous les formons, et nous restons joignables après la livraison.</x-kh.card-accent>
        </div>
    </x-kh.section>

    <x-kh.section id="artefacts" tone="fog" title="Artefacts" lead="Fragments de produit flottants. Tout chiffre porte l'étiquette « Démo ».">
        <div class="grid items-start gap-6 md:grid-cols-2 lg:grid-cols-4">
            <x-kh.artifact label="Brief" float :i="0">
                <p class="font-semibold">KLK-2026-0042</p>
                <p class="mt-1 text-slate">Boutique, 3 points de vente</p>
                <p class="mt-3 text-tag font-medium uppercase text-slate">Modules recommandés</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <x-kh.badge tone="blue">Stock &amp; caisse</x-kh.badge><x-kh.badge tone="blue">Multi-sites</x-kh.badge><x-kh.badge tone="blue">Mobile Money</x-kh.badge>
                </div>
            </x-kh.artifact>
            <x-kh.artifact label="Alerte stock" float :i="1">
                <p class="flex items-start gap-2"><x-kh.icon name="triangle-alert" :size="18" class="mt-0.5 text-kh-red-600" /><span><strong class="font-semibold">Riz 25 kg</strong> : 4 sacs restants (seuil 10)</span></p>
            </x-kh.artifact>
            <x-kh.artifact label="Ventes 7 jours" float :i="2">
                <svg viewBox="0 0 160 48" class="h-12 w-full text-kh-blue-500" aria-hidden="true" focusable="false"><polyline fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="0,40 26,32 52,36 78,22 104,26 130,12 160,8" /></svg>
            </x-kh.artifact>
            <x-kh.artifact :demo="false" float :i="3">
                <div class="flex items-center gap-2 rounded-input border border-line px-3 py-2">
                    <span class="flex-1 text-slate">Quel logiciel pour ma boutique ?</span>
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-kh-blue-700 text-white"><x-kh.icon name="arrow-up" :size="16" /></span>
                </div>
            </x-kh.artifact>
        </div>
    </x-kh.section>

    <x-kh.section id="formulaires" title="Formulaires" lead="Label toujours visible, erreur liée au champ, cible ≥ 44 px.">
        <form class="grid max-w-xl gap-5" action="#formulaires">
            <x-kh.input name="sg_name" label="Votre nom" required autocomplete="name" />
            <x-kh.input name="sg_phone" label="Téléphone / WhatsApp" type="tel" required value="+223 " hint="Exemple : 74 66 64 89" />
            <x-kh.input name="sg_email" label="E-mail" type="email" />
            <x-kh.input name="sg_error" label="Champ en erreur" required error="Ce numéro ne semble pas valide. Exemple : 74 66 64 89" />
            <x-kh.input name="sg_disabled" label="Champ désactivé" disabled value="Lecture seule" />
        </form>
    </x-kh.section>

    <x-kh.section id="icones" tone="fog" title="Icônes" lead="Lucide (ISC), trait 1,75 px, 20 ou 24 px.">
        <ul class="grid grid-cols-3 gap-3 sm:grid-cols-6 lg:grid-cols-8">
            @foreach ($icons as $icon)
                <li class="flex flex-col items-center gap-2 rounded-input bg-white p-3 text-center">
                    <x-kh.icon :name="$icon" :size="24" class="text-kh-blue-700" />
                    <code class="text-[0.75rem] break-all text-slate">{{ $icon }}</code>
                </li>
            @endforeach
            <li class="flex flex-col items-center gap-2 rounded-input bg-white p-3 text-center">
                <x-kh.icon name="brand:whatsapp" :size="24" class="text-whatsapp" />
                <code class="text-[0.75rem] text-slate">brand:whatsapp</code>
            </li>
        </ul>
    </x-kh.section>

    <x-kh.section id="motif" title="Sphère de points" lead="SVG calculé côté serveur. Décor : aria-hidden.">
        <div class="flex flex-wrap items-end gap-8">
            <x-kh.halftone :size="200" tone="blue-100" animate />
            <x-kh.halftone :size="160" tone="blue-200" animate />
            <x-kh.halftone :size="120" tone="blue-700" />
            <div class="rounded-card bg-kh-blue-950 p-4"><x-kh.halftone :size="120" tone="blue-900" /></div>
        </div>
    </x-kh.section>

    <x-kh.section id="animations" tone="fog" title="Animations" lead="Apparition au scroll en cascade (6 éléments max), désactivée si « réduire les animations ».">
        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @for ($i = 0; $i < 6; $i++)
                <div class="rounded-card bg-white p-6 text-center" data-reveal style="--i: {{ $i }}">{{ $i + 1 }}</div>
            @endfor
        </div>
    </x-kh.section>

    <x-kh.section tone="blue" title="Votre prochain logiciel commence par *une bonne question*" lead="3 minutes pour clarifier votre besoin. Un conseiller pour en parler.">
        <x-slot:decor><x-kh.halftone :size="320" tone="blue-900" class="absolute -right-16 -bottom-20" /></x-slot:decor>
        <div class="flex flex-wrap gap-3">
            <x-kh.button variant="inverse" :href="url('/diagnostic?entry=home')">Faire mon diagnostic gratuit</x-kh.button>
            <x-kh.button variant="whatsapp-inverse" :href="\App\Domain\Platform\Support\WhatsAppLink::for('styleguide')" />
        </div>
    </x-kh.section>
</x-layouts.public>
