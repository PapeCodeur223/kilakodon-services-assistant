{{-- Logo d'origine, recadré uniquement (resources/brand/logo-kilakodon.png). Ne jamais le recolorer. --}}
<picture>
    <source type="image/webp" srcset="{{ asset('images/brand/logo-kilakodon-1x.webp') }} 1x, {{ asset('images/brand/logo-kilakodon-2x.webp') }} 2x">
    <img
        src="{{ asset('images/brand/logo-kilakodon-1x.png') }}"
        srcset="{{ asset('images/brand/logo-kilakodon-2x.png') }} 2x"
        width="70" height="44"
        alt="Kilakodon Services"
        {{ $attributes->class('h-9 w-auto lg:h-11') }}
    >
</picture>
