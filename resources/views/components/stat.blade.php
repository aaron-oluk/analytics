@props(['label', 'value', 'icon' => 'bx-user'])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <i class="bx {{ $icon }} text-base text-zinc-400"></i>
        <span>{{ $label }}</span>
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-[1.75rem]">{{ $value }}</p>
</div>
