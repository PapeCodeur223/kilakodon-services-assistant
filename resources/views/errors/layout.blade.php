{{-- Gabarit des erreurs serveur : en-tête réduit, aucune dépendance à la base. --}}
<x-layouts.public :title="$title" :noindex="true" chrome="minimal">
    <div class="kh-container flex h-[72px] items-center">
        <a href="{{ url('/') }}" aria-label="Kilakodon Services — accueil"><x-kh.logo /></a>
    </div>
    <section class="kh-section relative overflow-hidden">
        <x-kh.halftone :size="420" class="absolute -right-32 top-0 hidden md:block" />
        <div class="kh-container relative">
            <x-kh.eyebrow>Erreur {{ $code }}</x-kh.eyebrow>
            <h1 class="mt-4 max-w-2xl text-h1">{{ \App\View\Support\Accent::html($heading) }}</h1>
            <p class="kh-prose mt-5 text-lead text-slate">{{ $message }}</p>
            <div class="mt-10 flex flex-wrap gap-3">
                <x-kh.button :href="url('/')">Retour à l'accueil</x-kh.button>
                <x-kh.button variant="whatsapp" :href="'https://wa.me/'.preg_replace('/\D+/', '', (string) (config('kh.settings')['contact.whatsapp_primary'] ?? '')).'?text='.rawurlencode(\App\Domain\Platform\Support\WhatsAppLink::GENERAL_MESSAGE)" />
            </div>
        </div>
    </section>
</x-layouts.public>
