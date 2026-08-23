<div class="flex h-16 shrink-0 items-center px-3" :class="sidebarExpanded ? 'lg:px-5' : 'lg:justify-center lg:px-2'">
    <a href="{{ route('sites.index') }}" class="flex items-center gap-2.5 text-zinc-900">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-white">
            <x-application-logo class="h-4 w-4 fill-current" />
        </span>
        <span class="text-[15px] font-semibold tracking-tight max-lg:inline" :class="sidebarExpanded ? 'lg:inline' : 'lg:hidden'">Analytics</span>
    </a>
</div>

<nav class="flex min-h-0 flex-1 flex-col px-3 pb-4" :class="sidebarExpanded ? '' : 'lg:px-2'">
    <div class="px-2 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 max-lg:block" :class="sidebarExpanded ? 'lg:block' : 'lg:hidden'">
        Workspace
    </div>
    <x-sidebar-link
        :href="route('sites.index')"
        icon="bx-grid-alt"
        :active="request()->routeIs('sites.index', 'dashboard')"
    >
        Sites
    </x-sidebar-link>
    <x-sidebar-link
        :href="route('sites.create')"
        icon="bx-plus-circle"
        :active="request()->routeIs('sites.create')"
    >
        Add site
    </x-sidebar-link>

    @if ($navSites->isNotEmpty())
        <div class="mt-5 px-2 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 max-lg:block" :class="sidebarExpanded ? 'lg:block' : 'lg:hidden'">
            Sites
        </div>
        <div class="min-h-0 flex-1 space-y-0.5" :class="sidebarExpanded ? 'overflow-y-auto' : 'overflow-visible'">
            @foreach ($navSites as $navSite)
                <x-sidebar-link
                    :href="route('sites.show', $navSite)"
                    icon="bx-globe"
                    :active="$currentSite && $currentSite->is($navSite)"
                >
                    {{ $navSite->name }}
                </x-sidebar-link>
            @endforeach
        </div>
    @endif

    <button
        type="button"
        @click="toggleExpanded()"
        class="mt-auto hidden items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 lg:flex"
        :class="sidebarExpanded ? 'justify-start' : 'justify-center px-0'"
        :aria-label="sidebarExpanded ? 'Collapse sidebar' : 'Expand sidebar'"
        :aria-expanded="sidebarExpanded"
    >
        <i class="bx text-lg" :class="sidebarExpanded ? 'bx-chevron-left' : 'bx-chevron-right'"></i>
        <span class="truncate" x-show="sidebarExpanded" x-cloak>Collapse</span>
    </button>
</nav>
