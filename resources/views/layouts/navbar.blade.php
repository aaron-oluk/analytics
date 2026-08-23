@php
    $initials = collect(preg_split('/\s+/', trim(Auth::user()->name)))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-zinc-200/80 bg-white/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button
            type="button"
            @click="toggleSidebar()"
            class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900"
            :aria-label="sidebarIsOpen() ? 'Close sidebar' : 'Open sidebar'"
            :aria-expanded="sidebarIsOpen()"
        >
            <i class="bx text-xl" :class="sidebarIsOpen() ? 'bx-x' : 'bx-menu'"></i>
            <span class="hidden sm:inline" x-text="sidebarIsOpen() ? 'Close sidebar' : 'Open sidebar'">Open sidebar</span>
        </button>

        <a href="{{ route('sites.index') }}" class="flex items-center gap-2 text-zinc-900 lg:hidden">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-teal-600 text-white">
                <x-application-logo class="h-3.5 w-3.5 fill-current" />
            </span>
            <span class="text-sm font-semibold tracking-tight">Analytics</span>
        </a>
    </div>

    <div class="flex items-center gap-3">
        <x-dropdown align="right" width="56">
            <x-slot name="trigger">
                <button type="button" class="flex items-center gap-2.5 rounded-full py-1 pe-2 ps-1 text-left transition hover:bg-zinc-100">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                        {{ $initials }}
                    </span>
                    <span class="hidden min-w-0 sm:block">
                        <span class="block max-w-[12rem] truncate text-sm font-medium text-zinc-900">{{ Auth::user()->name }}</span>
                        <span class="block max-w-[12rem] truncate text-xs text-zinc-500">{{ Auth::user()->email }}</span>
                    </span>
                    <i class="bx bx-chevron-down text-base text-zinc-400"></i>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="border-b border-zinc-100 px-4 py-3 sm:hidden">
                    <div class="truncate text-sm font-medium text-zinc-900">{{ Auth::user()->name }}</div>
                    <div class="truncate text-xs text-zinc-500">{{ Auth::user()->email }}</div>
                </div>
                <x-dropdown-link :href="route('profile.edit')">
                    Profile
                </x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Log out
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
