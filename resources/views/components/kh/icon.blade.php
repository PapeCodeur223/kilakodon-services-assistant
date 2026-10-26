@if ($isBrand())
<svg {{ $attributes->class('shrink-0') }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="currentColor" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false">{!! $inner() !!}</svg>
@else
<svg {{ $attributes->class('shrink-0') }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false">{!! $inner() !!}</svg>
@endif
