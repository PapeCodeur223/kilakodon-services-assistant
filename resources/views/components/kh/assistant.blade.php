{{--
    Lanceur de l'assistant (UX §4.3). Rendu seulement si l'assistant est activé (interrupteur, budget) ;
    caché sans JavaScript (attribut hidden retiré par resources/js/assistant-launcher.js). Le panneau
    lui-même est chargé à la première ouverture (import dynamique) : aucun coût pour les autres visiteurs.
--}}
@php
    $settings = app(\App\Domain\Platform\Services\Settings::class);
    $consents = (array) $settings->get('consent.texts', []);
    $config = [
        'endpoints' => [
            'start' => route('assistant.start'),
            'message' => route('assistant.message', ['conversation' => '__ID__']),
            'revise' => route('assistant.actions.revise', ['proposal' => '__ID__']),
            'confirm' => route('assistant.actions.confirm', ['proposal' => '__ID__']),
            'cancel' => route('assistant.actions.cancel', ['proposal' => '__ID__']),
            'feedback' => route('assistant.feedback', ['conversation' => '__ID__', 'message' => '__MSG__']),
        ],
        'csrf' => csrf_token(),
        'maxChars' => (int) config('assistant.guards.max_message_chars', 1000),
        'consentProcessing' => (string) ($consents['processing'] ?? ''),
        'consentMarketing' => (string) ($consents['marketing'] ?? ''),
        'channels' => \App\Domain\Crm\Enums\AppointmentChannel::options(),
        'periods' => \App\Domain\Crm\Enums\SlotPeriod::options(),
        'minDate' => now(config('kh.display_timezone'))->addDay()->toDateString(),
        'maxDate' => now(config('kh.display_timezone'))->addMonths(3)->toDateString(),
        'privacyUrl' => route('legal.privacy'),
    ];
@endphp
<div data-assistant data-config='@json($config)'>
    <button type="button" hidden data-assistant-launcher aria-haspopup="dialog" aria-expanded="false"
        class="fixed bottom-4 right-4 z-40 inline-flex min-h-12 items-center gap-2 rounded-full bg-kh-blue-700 px-5 py-3 font-semibold text-white shadow-pop transition-transform duration-200 hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500 sm:bottom-6 sm:right-6 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-3">
        <x-kh.icon name="sparkles" :size="20" />
        Une question ?
    </button>
</div>
