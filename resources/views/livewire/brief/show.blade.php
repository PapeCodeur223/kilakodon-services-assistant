@php
    use App\Domain\Platform\Support\WhatsAppLink;

    $declared = $version->declared;
    $recommended = $version->recommended;
    $tz = config('kh.display_timezone');
    $answersByStep = collect($declared['answers'] ?? [])->groupBy('step');
    $stepTitles = \App\Livewire\Diagnostic\Wizard::STEP_TITLES;
    $questionLabels = collect($declared['answers'] ?? [])->pluck('question', 'key');
@endphp
<article class="mx-auto max-w-[820px]" x-data x-on:kh-brief-validated.window="window.scrollTo({ top: 0, behavior: 'smooth' })">
    <div class="kh-step-enter rounded-card bg-white shadow-artifact">
        {{-- En-tête du document --}}
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line p-6 sm:p-10">
            <div>
                <x-kh.logo class="h-9" />
                <p class="mt-6 text-tag font-semibold uppercase text-slate">Brief de projet</p>
                <h1 class="mt-1 font-display text-h2 font-extrabold text-ink">{{ $brief->reference }}</h1>
                <p class="mt-1 text-small text-slate">
                    {{ $declared['contact']['company'] ?? $declared['contact']['full_name'] }} · créé le {{ $brief->created_at?->timezone($tz)->translatedFormat('j F Y') }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-2">
                @if ($brief->isValidated())
                    <x-kh.badge tone="success" icon="circle-check">Validé le {{ $brief->validated_at?->timezone($tz)->translatedFormat('j F Y à H:i') }}</x-kh.badge>
                @else
                    <x-kh.badge tone="warning" icon="pencil">À relire et valider</x-kh.badge>
                @endif
                <span class="text-tag normal-case tracking-normal text-slate">Version {{ $version->version }}</span>
            </div>
        </header>

        @if ($brief->isValidated() && ! $editing)
            <div class="border-b border-line bg-success-50 p-6 sm:px-10" role="status">
                <p class="flex items-center gap-2 font-semibold text-success-600"><x-kh.icon name="circle-check" :size="20" /> Votre brief est validé.</p>
                <p class="mt-1 text-small text-ink">Notre équipe l'a bien reçu et revient vers vous pour en discuter. Gardez la référence {{ $brief->reference }}.</p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <x-kh.button :href="route('brief.pdf', ['token' => $token])" :arrow="false">
                        <x-kh.icon name="download" :size="18" /> Télécharger le PDF
                    </x-kh.button>
                    <x-kh.button variant="whatsapp" :href="WhatsAppLink::forBrief($brief->reference)">Continuer sur WhatsApp</x-kh.button>
                    <x-kh.button variant="ghost" :href="route('appointment', ['brief' => $token])" :arrow="false">
                        <x-kh.icon name="calendar-check" :size="18" /> Demander un rendez-vous
                    </x-kh.button>
                </div>
            </div>
        @endif

        <div class="space-y-12 p-6 sm:p-10">
            {{-- 1. Déclaré par le prospect --}}
            <section aria-labelledby="declared-title">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="declared-title" class="font-display text-h3 font-extrabold text-ink">Ce que vous nous avez dit</h2>
                    @if (! $editing)
                        <button type="button" wire:click="edit" class="inline-flex min-h-11 items-center gap-1.5 rounded-full px-3 text-small font-semibold text-kh-blue-700 hover:bg-kh-blue-50 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                            <x-kh.icon name="pencil" :size="16" /> Corriger
                        </button>
                    @endif
                </div>

                <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-tag font-semibold uppercase text-slate">Contact</dt>
                        <dd class="mt-1 text-ink">{{ $declared['contact']['full_name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-tag font-semibold uppercase text-slate">Entreprise</dt>
                        <dd class="mt-1 text-ink">
                            @if ($editing)
                                <label for="brief-company" class="sr-only">Entreprise</label>
                                <input id="brief-company" type="text" wire:model="company" maxlength="160" class="h-12 w-full rounded-input border border-line bg-white px-3 text-body focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500" placeholder="Nom de votre entreprise">
                            @else
                                {{ $declared['contact']['company'] ?? '—' }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @foreach ($answersByStep as $step => $answers)
                    <h3 class="mt-8 text-small font-bold text-kh-blue-700">{{ $stepTitles[$step] ?? '' }}</h3>
                    <dl class="mt-3 divide-y divide-line rounded-input border border-line">
                        @foreach ($answers as $answer)
                            <div class="grid gap-1 p-4 sm:grid-cols-[1fr_1fr] sm:gap-6">
                                <dt class="text-small text-slate">{{ $answer['question'] }}</dt>
                                <dd class="text-small font-medium text-ink">{{ $answer['answer'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endforeach

                <div class="mt-8">
                    <h3 class="text-small font-bold text-kh-blue-700">Vos précisions</h3>
                    @if ($editing)
                        <label for="brief-note" class="sr-only">Vos précisions</label>
                        <textarea id="brief-note" wire:model="contextNote" rows="4" maxlength="{{ \App\Domain\Diagnostic\Engine\Catalog::TEXT_MAX_LENGTH }}"
                            @error('contextNote') aria-invalid="true" aria-describedby="brief-note-error" @enderror
                            class="mt-2 w-full rounded-input border border-line bg-white px-4 py-3 text-body focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500"
                            placeholder="Une contrainte, un outil à garder, un délai souhaité…"></textarea>
                        @error('contextNote')<p id="brief-note-error" class="mt-1 text-small text-kh-red-600">{{ $message }}</p>@enderror
                    @else
                        <p class="mt-2 whitespace-pre-line text-ink">{{ $declared['context_note'] ?? 'Aucune précision ajoutée.' }}</p>
                    @endif
                </div>
            </section>

            {{-- 2. Recommandé par les règles (lecture seule) --}}
            <section aria-labelledby="reco-title" class="rounded-card bg-fog p-6 sm:p-8">
                <h2 id="reco-title" class="font-display text-h3 font-extrabold text-ink">Ce que nous recommandons</h2>
                <p class="mt-1 text-small text-slate">Calculé à partir de vos réponses, selon nos règles d'analyse.</p>

                <p class="mt-6 text-tag font-semibold uppercase text-slate">Niveau d'équipement numérique</p>
                <p class="mt-1 text-h3 font-bold text-kh-blue-700">{{ $recommended['maturity']['label'] }} <span class="text-small font-normal text-slate">(niveau {{ $recommended['maturity']['level'] }} sur 4)</span></p>
                <p class="mt-1 text-small text-ink">{{ $recommended['maturity']['explanation'] }}</p>

                @if (($recommended['pains'] ?? []) !== [])
                    <p class="mt-6 text-tag font-semibold uppercase text-slate">Vos priorités</p>
                    <ol class="mt-2 list-decimal space-y-1 ps-5 text-ink">
                        @foreach ($recommended['pains'] as $pain)
                            <li><span class="font-semibold">{{ $pain['label'] }}</span>@if ($pain['description'] !== '') — {{ $pain['description'] }}@endif</li>
                        @endforeach
                    </ol>
                @endif

                <p class="mt-6 text-tag font-semibold uppercase text-slate">Modules recommandés</p>
                @if (($recommended['recommendations'] ?? []) === [])
                    <p class="mt-2 text-ink">{{ $recommended['no_recommendation_message'] ?? '' }}</p>
                @else
                    <ul class="mt-2 grid gap-3">
                        @foreach ($recommended['recommendations'] as $reco)
                            <li class="rounded-input bg-white p-4">
                                <p class="font-semibold text-ink">{{ $reco['label'] }}</p>
                                <details class="kh-disclosure mt-1">
                                    <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 text-small font-semibold text-kh-blue-700">Pourquoi ? <x-kh.icon name="chevron-down" :size="16" class="kh-chevron" /></summary>
                                    <ul class="mt-1 space-y-2 border-s-2 border-kh-blue-100 ps-4">
                                        @foreach ($reco['reasons'] as $reason)
                                            @if ($reason['explanation'] !== '')
                                                <li class="text-small text-ink">{{ $reason['explanation'] }}
                                                    <span class="block text-tag normal-case tracking-normal text-slate">D'après : {{ collect($reason['question_keys'])->map(fn ($k) => $questionLabels[$k] ?? $k)->implode(' · ') }}</span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </details>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (($recommended['clarifications'] ?? []) !== [])
                    <p class="mt-6 text-tag font-semibold uppercase text-slate">À clarifier ensemble</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-small text-ink">
                        @foreach ($recommended['clarifications'] as $clarification)
                            <li>{{ $clarification['label'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- 3. Résumé (modifiable, source affichée) --}}
            <section aria-labelledby="summary-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="summary-title" class="font-display text-h3 font-extrabold text-ink">Résumé</h2>
                    <x-kh.badge :icon="$version->summary_source->value === 'ai' ? 'sparkles' : null">{{ $version->summary_source->label() }}</x-kh.badge>
                </div>
                @if ($editing)
                    <label for="brief-summary" class="sr-only">Résumé</label>
                    <textarea id="brief-summary" wire:model="summary" rows="7" maxlength="{{ \App\Domain\Brief\Actions\ValidateBrief::SUMMARY_MAX }}"
                        @error('summary') aria-invalid="true" aria-describedby="brief-summary-error" @enderror
                        class="mt-4 w-full rounded-input border border-line bg-white px-4 py-3 text-body leading-relaxed focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500"></textarea>
                    @error('summary')<p id="brief-summary-error" class="mt-1 text-small text-kh-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-small text-slate">Reformulez librement : c'est votre document.</p>

                    <label for="brief-comment" class="mt-6 block text-small font-medium text-ink">Un message pour notre équipe <span class="font-normal text-slate">(facultatif)</span></label>
                    <textarea id="brief-comment" wire:model="comment" rows="3" maxlength="{{ \App\Domain\Brief\Actions\ValidateBrief::COMMENT_MAX }}"
                        class="mt-2 w-full rounded-input border border-line bg-white px-4 py-3 text-body focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500"></textarea>
                @else
                    <p class="mt-4 whitespace-pre-line text-body text-ink">{{ $version->summary }}</p>
                @endif
            </section>

            @if ($editing)
                <div class="flex flex-col-reverse gap-3 border-t border-line pt-8 sm:flex-row sm:justify-end">
                    @if ($brief->isValidated())
                        <x-kh.button variant="ghost" wire:click="cancel">Annuler</x-kh.button>
                    @endif
                    <x-kh.button wire:click="validateBrief" wire:loading.attr="disabled" wire:target="validateBrief">
                        <span wire:loading.remove wire:target="validateBrief">Valider mon brief</span>
                        <span wire:loading wire:target="validateBrief">Validation…</span>
                    </x-kh.button>
                </div>
            @endif
        </div>

        <footer class="border-t border-line px-6 py-5 text-small text-slate sm:px-10">
            Document de travail non contractuel. Lien privé valable jusqu'au {{ $brief->access_expires_at->timezone($tz)->translatedFormat('j F Y') }}.
        </footer>
    </div>
</article>
