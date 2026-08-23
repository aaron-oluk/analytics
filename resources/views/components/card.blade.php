@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white ring-1 ring-zinc-200/80 '.$padding]) }}>
    {{ $slot }}
</div>
