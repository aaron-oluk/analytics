@props(['active' => false, 'icon' => null, 'href'])

@php
    $classes = $active
        ? 'bg-teal-50 text-teal-800'
        : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group relative flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition '.$classes]) }}
    :class="sidebarExpanded ? 'lg:justify-start' : 'lg:justify-center lg:px-0'"
>
    @if ($icon)
        <i class="bx {{ $icon }} shrink-0 text-lg {{ $active ? 'text-teal-700' : 'text-zinc-400' }}"></i>
    @endif

    <span class="truncate max-lg:inline" :class="sidebarExpanded ? 'lg:inline' : 'lg:hidden'">{{ $slot }}</span>

    <span
        class="pointer-events-none invisible absolute start-full top-1/2 z-50 ms-3 -translate-y-1/2 whitespace-nowrap rounded-lg bg-zinc-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 max-lg:!hidden"
        :class="sidebarExpanded ? 'lg:hidden' : ''"
    >{{ $slot }}</span>
</a>
