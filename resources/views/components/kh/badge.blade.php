@props(['tone' => 'neutral', 'icon' => null])
<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-tag font-medium normal-case tracking-normal',
    'bg-mist text-slate' => $tone === 'neutral',
    'bg-kh-blue-100 text-kh-blue-700' => $tone === 'blue',
    'bg-kh-red-50 text-kh-red-600' => $tone === 'red',
    'bg-success-50 text-success-600' => $tone === 'success',
    'bg-warning-50 text-warning-600' => $tone === 'warning',
]) }}>
    @if ($icon)
        <x-kh.icon :name="$icon" :size="14" />
    @endif
    {{ $slot }}
</span>
