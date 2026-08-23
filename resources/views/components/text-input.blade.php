@props(['disabled' => false])

@if ($attributes->get('type') === 'password')
    <div x-data="{ show: false }" class="relative">
        <input
            :type="show ? 'text' : 'password'"
            @disabled($disabled)
            {{ $attributes->merge(['class' => 'border-zinc-300 focus:border-teal-600 focus:ring-teal-600 rounded-xl shadow-sm pr-10']) }}
        >

        <button
            type="button"
            tabindex="-1"
            @click="show = !show"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 focus:outline-none"
            :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
        >
            <i class="bx bx-show text-lg" x-show="!show"></i>
            <i class="bx bx-hide text-lg" x-show="show" x-cloak></i>
        </button>
    </div>
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => 'border-zinc-300 focus:border-teal-600 focus:ring-teal-600 rounded-xl shadow-sm']) }}>
@endif
