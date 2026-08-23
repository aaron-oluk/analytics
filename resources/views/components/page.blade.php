@props(['width' => 'max-w-6xl'])

<div {{ $attributes->merge(['class' => $width.' mx-auto px-4 py-8 sm:px-6 sm:py-10 lg:px-8 space-y-6']) }}>
    {{ $slot }}
</div>
