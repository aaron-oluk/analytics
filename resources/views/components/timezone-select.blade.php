@props(['name' => 'timezone', 'selected' => 'UTC'])

@php
    $timezones = timezone_identifiers_list();
@endphp

<div
    x-data="{
        open: false,
        query: '',
        selected: @js($selected),
        options: @js($timezones),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (q === '') return this.options;
            return this.options.filter((tz) => tz.toLowerCase().includes(q));
        },
        choose(tz) {
            this.selected = tz;
            this.query = '';
            this.open = false;
        },
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selected">

    <button
        type="button"
        {{ $attributes->merge(['class' => 'w-full flex items-center justify-between border border-zinc-300 focus:border-teal-600 focus:ring-teal-600 rounded-xl shadow-sm px-3 py-2 text-left text-sm bg-white']) }}
        @click="open = !open; query = ''; $nextTick(() => $refs.search.focus())"
        :aria-expanded="open"
        aria-haspopup="listbox"
    >
        <span x-text="selected"></span>
        <i class="bx bx-chevron-down text-gray-400"></i>
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-10 mt-1 w-full bg-white border border-zinc-200 rounded-xl shadow-lg"
    >
        <div class="p-2 border-b border-zinc-100">
            <input
                type="text"
                x-ref="search"
                x-model="query"
                x-on:keydown.escape="open = false"
                placeholder="Search timezones..."
                class="w-full border-zinc-300 focus:border-teal-600 focus:ring-teal-600 rounded-xl text-sm"
            >
        </div>

        <ul role="listbox" class="max-h-60 overflow-auto py-1 text-sm">
            <template x-for="tz in filtered" :key="tz">
                <li
                    role="option"
                    @click="choose(tz)"
                    :aria-selected="tz === selected"
                    class="px-3 py-1.5 cursor-pointer hover:bg-teal-50"
                    :class="tz === selected ? 'bg-teal-50 font-medium' : ''"
                    x-text="tz"
                ></li>
            </template>

            <li x-show="filtered.length === 0" x-cloak class="px-3 py-1.5 text-gray-400">
                No matching timezones
            </li>
        </ul>
    </div>
</div>
