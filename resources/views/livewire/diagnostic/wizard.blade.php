@php
    use App\Domain\Diagnostic\Engine\Catalog;
    use App\Domain\Platform\Support\WhatsAppLink;

    $isLastStep = $step >= max($steps);
    $whatsapp = WhatsAppLink::for('diagnostic', 'Bonjour Kilakodon, je viens de faire le diagnostic sur votre site. Pouvons-nous en discuter ?');
@endphp
<div
    class="pb-32 sm:pb-16"
    x-data
    x-on:kh-step-changed.window="$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); $refs.heading?.focus({ preventScroll: true }) })"
    x-on:kh-step-invalid.window="$nextTick(() => document.querySelector('[data-missing]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
>
    {{-- Annonce des changements d'étape aux lecteurs d'écran (UX §9). --}}
    <p class="sr-only" aria-live="polite">
        @if ($screen === 'result') Votre diagnostic est prêt. @else Étape {{ $step }} sur {{ count($steps) }} : {{ $stepTitles[$step] ?? '' }} @endif
    </p>

    @error('session')
        <div class="mb-6 flex items-start gap-3 rounded-input bg-kh-red-50 p-4 text-small text-kh-red-600" role="alert">
            <x-kh.icon name="circle-alert" :size="20" class="mt-0.5" />{{ $message }}
        </div>
    @enderror

    @if ($screen === 'questions')
        {{-- Stepper collant --}}
        <nav aria-label="Progression du diagnostic" class="sticky top-0 z-20 -mx-4 mb-8 border-b border-line bg-canvas/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6">
            <ol class="flex items-center gap-2">
                @foreach ($steps as $s)
                    <li class="flex flex-1 flex-col gap-2" @if ($s === $step) aria-current="step" @endif>
                        <span class="block h-1.5 overflow-hidden rounded-full bg-mist">
                            <span @class([
                                'block h-full origin-left rounded-full bg-kh-blue-700 transition-transform duration-500 ease-out motion-reduce:transition-none',
                                'scale-x-100' => $s <= $step,
                                'scale-x-0' => $s > $step,
                            ])></span>
                        </span>
                        <span @class(['hidden text-tag font-semibold uppercase sm:block', 'text-kh-blue-700' => $s === $step, 'text-ink' => $s < $step, 'text-smoke' => $s > $step])>
                            {{ $stepTitles[$s] ?? 'Étape '.$s }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div wire:key="step-{{ $step }}" class="kh-step-enter">
            <p class="text-tag font-semibold uppercase text-kh-blue-700">Étape {{ $step }} sur {{ count($steps) }}</p>
            <h1 x-ref="heading" tabindex="-1" class="mt-2 font-display text-h2 font-extrabold text-ink focus:outline-none">{{ $stepTitles[$step] ?? '' }}</h1>

            <div class="mt-10 space-y-10">
                @foreach ($questions as $question)
                    @php
                        $value = $answers[$question->key] ?? null;
                        $isUnknown = $value === Catalog::UNKNOWN;
                        $isMissing = in_array($question->key, $missing, true);
                        $errorId = 'q-'.$question->key.'-error';
                        $hasError = $isMissing || $errors->has('answers.'.$question->key);
                    @endphp
                    <fieldset wire:key="q-{{ $question->key }}" @if ($isMissing) data-missing @endif @if ($hasError) aria-describedby="{{ $errorId }}" @endif>
                        <legend class="text-h3 font-bold text-ink">
                            {{ $question->label }}
                            @unless ($question->required)
                                <span class="text-small font-normal text-slate">(facultatif)</span>
                            @endunless
                        </legend>
                        @if ($question->help)
                            <p class="mt-1.5 text-small text-slate">{{ $question->help }}</p>
                        @endif

                        @if ($question->type === 'text')
                            <label for="q-{{ $question->key }}" class="sr-only">{{ $question->label }}</label>
                            <textarea
                                id="q-{{ $question->key }}"
                                wire:model.blur="answers.{{ $question->key }}"
                                rows="4"
                                maxlength="{{ Catalog::TEXT_MAX_LENGTH }}"
                                class="mt-4 w-full rounded-input border border-line bg-white px-4 py-3 text-body text-ink transition-colors hover:border-kh-blue-200 focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500"
                                placeholder="Votre contexte, une contrainte, un outil à garder…"
                            ></textarea>
                            <p class="mt-1.5 text-small text-slate">{{ Catalog::TEXT_MAX_LENGTH }} caractères au maximum. Ce texte figurera tel quel dans votre brief.</p>
                        @else
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($optionsFor($question) as $option)
                                    @php
                                        $checked = $question->isMulti()
                                            ? is_array($value) && in_array($option['value'], $value, true)
                                            : $value === $option['value'];
                                        $inputId = 'q-'.$question->key.'-'.$option['value'];
                                    @endphp
                                    <label for="{{ $inputId }}" wire:key="{{ $inputId }}" @class([
                                        'kh-choice group relative flex min-h-14 cursor-pointer items-center gap-3 rounded-input border-2 bg-white px-4 py-3 text-body transition-[border-color,background-color,transform] duration-150 active:scale-[0.99]',
                                        'border-kh-blue-700 bg-kh-blue-50 text-kh-blue-900' => $checked,
                                        'border-line text-ink hover:border-kh-blue-200' => ! $checked,
                                    ])>
                                        @if ($question->isMulti())
                                            <input id="{{ $inputId }}" type="checkbox" class="peer sr-only" @checked($checked)
                                                wire:click="toggle('{{ $question->key }}', '{{ $option['value'] }}')">
                                            <span @class(['flex size-5 shrink-0 items-center justify-center rounded-md border-2', 'border-kh-blue-700 bg-kh-blue-700 text-white' => $checked, 'border-smoke' => ! $checked]) aria-hidden="true">
                                                @if ($checked)<x-kh.icon name="check" :size="14" />@endif
                                            </span>
                                        @else
                                            <input id="{{ $inputId }}" type="radio" class="peer sr-only" name="q-{{ $question->key }}" value="{{ $option['value'] }}"
                                                wire:model.live="answers.{{ $question->key }}">
                                            <span @class(['flex size-5 shrink-0 items-center justify-center rounded-full border-2', 'border-kh-blue-700' => $checked, 'border-smoke' => ! $checked]) aria-hidden="true">
                                                @if ($checked)<span class="size-2.5 rounded-full bg-kh-blue-700"></span>@endif
                                            </span>
                                        @endif
                                        <span class="font-medium">{{ $option['label'] }}</span>
                                        <span class="pointer-events-none absolute inset-0 rounded-input peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-kh-blue-500" aria-hidden="true"></span>
                                    </label>
                                @endforeach
                            </div>

                            @if ($question->allowUnknown)
                                <button type="button" wire:click="unknown('{{ $question->key }}')" aria-pressed="{{ $isUnknown ? 'true' : 'false' }}" @class([
                                    'mt-3 inline-flex min-h-11 items-center gap-2 rounded-full border-2 px-4 text-small font-semibold transition-colors focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500',
                                    'border-kh-blue-700 bg-kh-blue-50 text-kh-blue-900' => $isUnknown,
                                    'border-line text-slate hover:border-kh-blue-200 hover:text-ink' => ! $isUnknown,
                                ])>
                                    <x-kh.icon name="circle-help" :size="18" />
                                    Je ne sais pas
                                </button>
                            @endif
                        @endif

                        @if ($hasError)
                            <p id="{{ $errorId }}" class="mt-3 flex items-start gap-1.5 text-small text-kh-red-600">
                                <x-kh.icon name="circle-alert" :size="18" class="mt-0.5" />
                                <span>{{ $errors->first('answers.'.$question->key) ?: 'Choisissez une réponse pour continuer.' }}</span>
                            </p>
                        @endif
                    </fieldset>
                @endforeach
            </div>

            <div class="mt-12">
                @if ($showResumeLink && $resumeUrl)
                    <div class="rounded-input bg-kh-blue-50 p-5 text-small text-kh-blue-900" role="status">
                        <p class="flex items-center gap-2 font-semibold"><x-kh.icon name="circle-check" :size="18" /> Vos réponses sont enregistrées.</p>
                        <p class="mt-2">Reprenez plus tard avec ce lien privé, valable 30 jours :</p>
                        <p class="mt-2 break-all rounded-lg bg-white px-3 py-2 font-mono text-[0.85rem] text-ink">{{ $resumeUrl }}</p>
                        <div class="mt-3">
                            <x-kh.button variant="whatsapp" size="sm" :href="'https://wa.me/?text='.rawurlencode('Mon diagnostic Kilakodon, à reprendre ici : '.$resumeUrl)">M'envoyer le lien sur WhatsApp</x-kh.button>
                        </div>
                    </div>
                @else
                    <button type="button" wire:click="saveForLater" class="inline-flex min-h-11 items-center gap-2 text-small font-semibold text-kh-blue-700 underline-offset-4 hover:underline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                        <x-kh.icon name="save" :size="18" />
                        Enregistrer et reprendre plus tard
                    </button>
                @endif
            </div>
        </div>

        {{-- Barre d'actions : collée en bas sur mobile (safe-area). --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white/95 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 backdrop-blur sm:static sm:mt-10 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
            <div class="mx-auto flex max-w-[720px] items-center justify-between gap-3">
                @if ($step > 1)
                    <x-kh.button variant="ghost" wire:click="back" wire:loading.attr="disabled">
                        <x-kh.icon name="arrow-left" :size="18" /> Retour
                    </x-kh.button>
                @else
                    <span></span>
                @endif
                <x-kh.button wire:click="next" wire:loading.attr="disabled" wire:target="next">
                    <span wire:loading.remove wire:target="next">{{ $isLastStep ? 'Voir mon diagnostic' : 'Continuer' }}</span>
                    <span wire:loading wire:target="next">Un instant…</span>
                </x-kh.button>
            </div>
        </div>
    @elseif ($outcome)
        @include('livewire.diagnostic.result')
    @endif
</div>
