@props(['active' => false, 'icon' => null, 'href'])

@php
    $classes = $active
        ? 'bg-teal-50 text-teal-800'
        : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition '.$classes]) }}>
    @if ($icon)
        <i class="bx {{ $icon }} text-lg {{ $active ? 'text-teal-700' : 'text-zinc-400' }}"></i>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
