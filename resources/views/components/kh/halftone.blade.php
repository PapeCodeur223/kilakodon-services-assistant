<svg {{ $attributes->class(['kh-halftone pointer-events-none', $toneClass()]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $size }} {{ $size }}" width="{{ $size }}" height="{{ $size }}" fill="currentColor" aria-hidden="true" focusable="false" @if ($animate) data-animate @endif>
@foreach ($points() as $p)<circle cx="{{ $p['cx'] }}" cy="{{ $p['cy'] }}" r="{{ $p['r'] }}" @if ($animate) style="--d: {{ $p['d'] }}" @endif/>@endforeach
</svg>
