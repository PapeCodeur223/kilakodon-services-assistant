{{-- Restitution (UX §5.4) : calculée par les règles ; seule la reformulation peut venir de l'IA. --}}
@php
    use App\Domain\Diagnostic\Engine\Catalog;

    $questionLabel = fn (string $key): string => isset($questionsByKey[$key]) ? $questionsByKey[$key]->label : $key;
@endphp
<div class="kh-step-enter">
    <p class="text-tag font-semibold uppercase text-kh-blue-700">Résultat</p>
    <h1 x-ref="heading" tabindex="-1" class="mt-2 font-display text-h1 font-extrabold text-ink focus:outline-none">Votre <span class="kh-accent">diagnostic</span></h1>

    {{-- Maturité : 4 points, le niveau atteint en bleu --}}
    <section aria-labelledby="maturity-title" class="mt-8 rounded-card border border-line bg-white p-6 sm:p-8">
        <h2 id="maturity-title" class="text-tag font-semibold uppercase text-slate">Votre niveau d'équipement numérique</h2>
        <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3">
            <p class="font-display text-h2 font-extrabold text-kh-blue-700">{{ $outcome->maturityLabel() }}</p>
            <ol class="flex items-center gap-2" aria-label="Niveau {{ $outcome->maturityLevel }} sur 4">
                @foreach (Catalog::MATURITY_LEVELS as $level => $info)
                    <li class="flex items-center gap-2">
                        <span @class(['block size-3.5 rounded-full', 'bg-kh-blue-700' => $level <= $outcome->maturityLevel, 'bg-line' => $level > $outcome->maturityLevel]) title="{{ $info['label'] }}"></span>
                    </li>
                @endforeach
            </ol>
        </div>
        <p class="mt-4 text-body text-ink">{{ $outcome->maturityExplanation() }}</p>
        <p class="mt-3 text-small text-slate">{{ Catalog::MATURITY_NOTE }}</p>
    </section>

    {{-- Priorités --}}
    <section aria-labelledby="pains-title" class="mt-12">
        <h2 id="pains-title" class="font-display text-h3 font-extrabold text-ink">Vos {{ count($outcome->pains) > 1 ? count($outcome->pains).' priorités' : 'priorités' }}</h2>
        @if ($outcome->pains === [])
            <p class="mt-3 text-slate">Vos priorités seront précisées lors de notre échange.</p>
        @else
            <ol class="mt-5 grid gap-3">
                @foreach ($outcome->pains as $i => $pain)
                    <li class="kh-item-enter flex items-start gap-4 rounded-input bg-fog p-4" style="--i: {{ $i }}">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-kh-blue-700 text-small font-bold text-white" aria-hidden="true">{{ $i + 1 }}</span>
                        <span>
                            <span class="block font-semibold text-ink">{{ $pain['label'] }}</span>
                            @if ($pain['description'] !== '')
                                <span class="block text-small text-slate">{{ $pain['description'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    {{-- Recommandations avec « Pourquoi ? » --}}
    <section aria-labelledby="reco-title" class="mt-12">
        <h2 id="reco-title" class="font-display text-h3 font-extrabold text-ink">Ce que nous recommandons</h2>
        @if ($outcome->needsConversation())
            <div class="mt-5 flex items-start gap-3 rounded-card border border-line bg-white p-6">
                <x-kh.icon name="lightbulb" :size="22" class="mt-0.5 text-kh-blue-700" />
                <p class="text-ink">{{ Catalog::NO_RECOMMENDATION_MESSAGE }} Un conseiller vous aide à y voir clair, sans engagement.</p>
            </div>
        @else
            <ul class="mt-5 grid gap-4">
                @foreach ($outcome->recommendations as $i => $reco)
                    <li class="kh-card kh-item-enter rounded-card border border-line bg-white p-6" style="--i: {{ $i }}">
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="text-h3 font-bold text-ink">{{ $reco['label'] }}</h3>
                            @if ($i === 0)
                                <x-kh.badge tone="blue">Prioritaire</x-kh.badge>
                            @endif
                        </div>
                        <details class="kh-disclosure mt-3 group/why">
                            <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 text-small font-semibold text-kh-blue-700">
                                Pourquoi ? <x-kh.icon name="chevron-down" :size="16" class="kh-chevron" />
                            </summary>
                            <ul class="mt-2 space-y-3 border-s-2 border-kh-blue-100 ps-4">
                                @foreach ($reco['reasons'] as $reason)
                                    @if ($reason['explanation'] !== '')
                                        <li>
                                            <p class="text-small text-ink">{{ $reason['explanation'] }}</p>
                                            <p class="mt-0.5 text-tag normal-case tracking-normal text-slate">D'après votre réponse à : {{ collect($reason['question_keys'])->map($questionLabel)->implode(' · ') }}</p>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </details>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- À clarifier --}}
    @if ($outcome->clarifications !== [])
        <section aria-labelledby="clarify-title" class="mt-12">
            <h2 id="clarify-title" class="font-display text-h3 font-extrabold text-ink">À clarifier ensemble</h2>
            <ul class="mt-5 grid gap-2">
                @foreach ($outcome->clarifications as $clarification)
                    <li class="flex items-start gap-3 text-ink">
                        <x-kh.icon name="circle-help" :size="20" class="mt-0.5 shrink-0 text-kh-blue-700" />
                        <span>{{ $clarification['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Résumé --}}
    @if ($summary)
        <section aria-labelledby="summary-title" class="mt-12 rounded-card bg-fog p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="summary-title" class="font-display text-h3 font-extrabold text-ink">En résumé</h2>
                @if ($summary['ai'])
                    <x-kh.badge icon="sparkles">Rédigé automatiquement</x-kh.badge>
                @endif
            </div>
            <p class="mt-4 text-body text-ink">{{ $summary['text'] }}</p>
        </section>
    @endif

    <div class="mt-8">
        <button type="button" wire:click="editAnswers" class="inline-flex min-h-11 items-center gap-2 text-small font-semibold text-kh-blue-700 underline-offset-4 hover:underline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
            <x-kh.icon name="pencil" :size="18" /> Modifier mes réponses
        </button>
    </div>

    {{-- Bloc de conversion collant --}}
    <div class="sticky bottom-0 z-20 -mx-4 mt-10 border-t border-line bg-white/95 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 backdrop-blur sm:mx-0 sm:rounded-card sm:border sm:p-6 sm:shadow-pop">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="hidden text-small text-slate sm:block">Recevez ce diagnostic sous forme de brief, à relire et à partager.</p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-kh.button wire:click="openBriefForm">Recevoir mon brief</x-kh.button>
                <x-kh.button variant="whatsapp" :href="$whatsapp" />
            </div>
        </div>
    </div>

    {{-- Formulaire de coordonnées : feuille modale (UX §5.4) --}}
    @if ($showBriefForm)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-kh-blue-975/60 sm:items-center sm:p-6"
            role="dialog" aria-modal="true" aria-labelledby="brief-form-title"
            x-data
            x-init="$nextTick(() => $el.querySelector('input:not([type=hidden])')?.focus())"
            x-on:keydown.escape.window="$wire.set('showBriefForm', false)"
        >
            <div class="kh-sheet max-h-[92dvh] w-full max-w-xl overflow-y-auto rounded-t-card bg-white p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-pop sm:rounded-card sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="brief-form-title" class="font-display text-h3 font-extrabold text-ink">Recevoir mon brief</h2>
                        <p class="mt-1 text-small text-slate">Vous pourrez le relire et le corriger avant de le valider.</p>
                    </div>
                    <button type="button" wire:click="$set('showBriefForm', false)" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-slate hover:bg-mist focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500" aria-label="Fermer">
                        <x-kh.icon name="x" :size="20" />
                    </button>
                </div>

                <form wire:submit="requestBrief" class="mt-6 grid gap-5" novalidate>
                    <x-honeypot livewire-model="extraFields" />
                    <x-kh.input name="fullName" wire:model="fullName" label="Votre nom" required autocomplete="name" :error="$errors->first('fullName')" />
                    <x-kh.input name="phone" wire:model="phone" label="Téléphone / WhatsApp" type="tel" required autocomplete="tel" inputmode="tel" hint="Exemple : 74 66 64 89 ou +223 74 66 64 89" :error="$errors->first('phone')" />
                    <x-kh.input name="email" wire:model="email" label="E-mail" type="email" autocomplete="email" :error="$errors->first('email')" />
                    <x-kh.input name="company" wire:model="company" label="Entreprise" autocomplete="organization" :error="$errors->first('company')" />

                    <fieldset class="grid gap-4 rounded-input bg-mist p-5">
                        <legend class="sr-only">Consentements</legend>
                        <x-kh.checkbox name="consentProcessing" wire:model="consentProcessing" required>{{ $consentTexts['processing'] ?? '' }}</x-kh.checkbox>
                        <x-kh.checkbox name="consentMarketing" wire:model="consentMarketing">{{ $consentTexts['marketing'] ?? '' }}</x-kh.checkbox>
                        @if ($privacyVisible)
                            <p class="ps-8 text-small text-slate">Voir notre <a href="{{ route('legal.privacy') }}" class="text-kh-blue-700 underline" target="_blank">politique de confidentialité</a>.</p>
                        @endif
                    </fieldset>

                    <x-kh.button type="submit" wire:loading.attr="disabled" wire:target="requestBrief">
                        <span wire:loading.remove wire:target="requestBrief">Recevoir mon brief</span>
                        <span wire:loading wire:target="requestBrief">Préparation du brief…</span>
                    </x-kh.button>
                </form>
            </div>
        </div>
    @endif
</div>
