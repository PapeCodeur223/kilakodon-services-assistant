@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Rendez-vous', 'url' => route('appointment')]];
    $privacyVisible = app(\App\Domain\Content\Services\ContentFinder::class)->query(\App\Domain\Content\Models\Page::class)->where('slug', 'confidentialite')->exists();
    $oldSlots = (array) old('slots', []);
@endphp
<x-layouts.public
    title="Demander un rendez-vous"
    description="Proposez jusqu'à trois créneaux pour échanger avec Kilakodon Services, à Bamako ou à distance : appel WhatsApp, téléphone, visioconférence ou rencontre sur place."
    :noindex="$briefToken !== null"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Rendez-vous" title="Parlons de votre projet, *de vive voix*" :breadcrumbs="$crumbs"
        lead="Proposez un ou plusieurs créneaux : un conseiller vous confirme le rendez-vous." />

    <section class="pb-20 lg:pb-28">
        <div class="kh-container grid gap-12 lg:grid-cols-[1.4fr_1fr]">
            <div>
                @if (session('appointment.success'))
                    <div class="mb-8 flex items-start gap-3 rounded-input bg-success-50 p-5 text-success-600" role="status">
                        <x-kh.icon name="circle-check" :size="22" class="mt-0.5" />
                        <p>{{ session('appointment.success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-8 flex items-start gap-3 rounded-input bg-kh-red-50 p-5 text-kh-red-600" role="alert">
                        <x-kh.icon name="circle-alert" :size="22" class="mt-0.5" />
                        <p>Certains champs sont à corriger ci-dessous.</p>
                    </div>
                @endif

                @if ($briefReference)
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full bg-kh-blue-50 px-4 py-2 text-small font-semibold text-kh-blue-700">
                        <x-kh.icon name="file-text" :size="18" /> Demande liée à votre brief {{ $briefReference }}
                    </p>
                @endif

                <form method="POST" action="{{ route('appointment.store') }}" class="grid gap-8" novalidate>
                    @csrf
                    <x-honeypot />
                    @if ($briefToken)
                        <input type="hidden" name="brief" value="{{ $briefToken }}">
                    @endif

                    <fieldset class="grid gap-6">
                        <legend class="mb-2 font-display text-h3 font-extrabold text-ink">Votre rendez-vous</legend>
                        <x-kh.input name="topic" label="Motif" required :value="$prefill['topic']" hint="Exemple : présenter notre gestion de stock, voir une démonstration…" />

                        <div>
                            <p id="channel-label" class="text-small font-medium text-ink">Format <span class="text-kh-red-600" aria-hidden="true">*</span></p>
                            <div class="mt-2 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-labelledby="channel-label" @error('channel') aria-describedby="channel-error" @enderror>
                                @foreach ($channels as $value => $label)
                                    <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-input border-2 border-line bg-white px-4 py-3 text-body has-checked:border-kh-blue-700 has-checked:bg-kh-blue-50 hover:border-kh-blue-200">
                                        <input type="radio" name="channel" value="{{ $value }}" class="size-5 accent-kh-blue-700" @checked(old('channel', 'whatsapp') === $value) required>
                                        <span class="font-medium">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('channel')<p id="channel-error" class="mt-1.5 text-small text-kh-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <p class="text-small font-medium text-ink">Créneaux souhaités <span class="font-normal text-slate">(1 à 3, heure de Bamako)</span></p>
                            <div class="mt-2 grid gap-3">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="grid gap-3 rounded-input bg-mist p-4 sm:grid-cols-[1fr_1fr]">
                                        <div class="flex flex-col gap-1.5">
                                            <label for="slot-{{ $i }}-date" class="text-small text-slate">Créneau {{ $i + 1 }}{{ $i === 0 ? '' : ' (facultatif)' }} — date</label>
                                            <input id="slot-{{ $i }}-date" type="date" name="slots[{{ $i }}][date]" min="{{ $minDate }}" max="{{ $maxDate }}"
                                                value="{{ $oldSlots[$i]['date'] ?? '' }}" @if ($i === 0) required @endif
                                                @error('slots.'.$i.'.date') aria-invalid="true" aria-describedby="slot-{{ $i }}-error" @enderror
                                                class="h-[52px] w-full rounded-input border border-line bg-white px-4 text-body text-ink focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500">
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label for="slot-{{ $i }}-period" class="text-small text-slate">Moment</label>
                                            <select id="slot-{{ $i }}-period" name="slots[{{ $i }}][period]"
                                                class="h-[52px] w-full rounded-input border border-line bg-white px-4 text-body text-ink focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500">
                                                <option value="">Choisissez…</option>
                                                @foreach ($periods as $value => $label)
                                                    <option value="{{ $value }}" @selected(($oldSlots[$i]['period'] ?? ($i === 0 ? 'morning' : '')) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if ($errors->has('slots.'.$i.'.date') || $errors->has('slots.'.$i.'.period'))
                                            <p id="slot-{{ $i }}-error" class="text-small text-kh-red-600 sm:col-span-2">{{ $errors->first('slots.'.$i.'.date') ?: $errors->first('slots.'.$i.'.period') }}</p>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-6">
                        <legend class="mb-2 font-display text-h3 font-extrabold text-ink">Vos coordonnées</legend>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <x-kh.input name="name" label="Votre nom" required autocomplete="name" :value="$prefill['name']" />
                            <x-kh.input name="phone" label="Téléphone / WhatsApp" type="tel" required autocomplete="tel" inputmode="tel" :value="$prefill['phone']" hint="Exemple : 74 66 64 89 ou +223 74 66 64 89" />
                        </div>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <x-kh.input name="email" label="E-mail" type="email" autocomplete="email" :value="$prefill['email']" hint="Pour recevoir la confirmation du rendez-vous." />
                            <x-kh.input name="company" label="Entreprise" autocomplete="organization" :value="$prefill['company']" />
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-4 rounded-input bg-mist p-5">
                        <legend class="sr-only">Consentements</legend>
                        <x-kh.checkbox name="consent_processing" required>{{ $consentTexts['processing'] ?? '' }}</x-kh.checkbox>
                        <x-kh.checkbox name="consent_marketing">{{ $consentTexts['marketing'] ?? '' }}</x-kh.checkbox>
                        @if ($privacyVisible)
                            <p class="ps-8 text-small text-slate">Voir notre <a href="{{ route('legal.privacy') }}" class="text-kh-blue-700 underline">politique de confidentialité</a>.</p>
                        @endif
                    </fieldset>

                    <div>
                        <x-kh.button type="submit">Envoyer ma demande</x-kh.button>
                        <p class="mt-3 text-small text-slate">Votre rendez-vous n'est confirmé qu'après l'appel ou le message d'un conseiller.</p>
                    </div>
                </form>
            </div>

            <aside class="space-y-5">
                <x-kh.card-accent tone="blue" title="Plus rapide : *WhatsApp*">
                    <p>Une question avant de fixer un rendez-vous ? Écrivez-nous directement.</p>
                    <div class="mt-5"><x-kh.button variant="whatsapp" :href="WhatsAppLink::for('appointment', 'Bonjour Kilakodon, je souhaite convenir d\'un rendez-vous.')" /></div>
                </x-kh.card-accent>
                <x-kh.card title="Comment ça se passe ?" icon="calendar-check">
                    <ol class="list-decimal space-y-2 ps-5">
                        <li>Vous proposez vos créneaux.</li>
                        <li>Un conseiller vous contacte pour en choisir un.</li>
                        <li>Vous recevez la confirmation, par e-mail si vous nous avez laissé votre adresse.</li>
                    </ol>
                </x-kh.card>
            </aside>
        </div>
    </section>
</x-layouts.public>
