@php
    use App\Domain\Platform\Support\WhatsAppLink;
@endphp
{{-- Lien de brief expiré ou révoqué (BRF-04, CONTENT §11). --}}
<x-layouts.public title="Lien expiré" description="Ce lien privé a expiré. Contactez Kilakodon Services avec votre référence pour en recevoir un nouveau." noindex chrome="none">
    <x-kh.focus-header />
    <div class="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-24">
        <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700">
            <x-kh.icon name="lock-keyhole" :size="28" />
        </span>
        <h1 class="mt-6 font-display text-h2 font-extrabold text-ink">Ce lien a <em>expiré</em>.</h1>
        <p class="mt-4 text-lead text-slate">Pour votre sécurité, les liens privés sont valables 30 jours. Contactez-nous avec votre référence pour en recevoir un nouveau.</p>
        <p class="mt-6 inline-flex rounded-full bg-mist px-4 py-2 font-mono text-small text-ink">Référence : {{ $reference }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <x-kh.button variant="whatsapp" :href="WhatsAppLink::for('brief', 'Bonjour Kilakodon, le lien de mon brief '.$reference.' a expiré. Pouvez-vous m\'en envoyer un nouveau ?')">Demander un nouveau lien</x-kh.button>
            <x-kh.button variant="ghost" :href="route('contact')">Nous écrire</x-kh.button>
        </div>
    </div>
</x-layouts.public>
