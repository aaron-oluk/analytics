@php
    $navSites = Auth::user()->sites()->latest()->get();
    $currentSite = request()->route('site');
@endphp

{{-- Desktop sidebar --}}
<aside
    class="fixed inset-y-0 start-0 z-30 hidden flex-col overflow-visible border-e border-zinc-200/80 bg-white transition-[width] duration-200 lg:flex"
    :class="sidebarExpanded ? 'w-64' : 'w-16'"
>
    @include('layouts.partials.sidebar-nav', ['navSites' => $navSites, 'currentSite' => $currentSite])
</aside>

{{-- Mobile drawer --}}
<div
    x-show="sidebarOpen"
    x-cloak
    class="relative z-40 lg:hidden"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="sidebarOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-zinc-900/40"
        @click="sidebarOpen = false"
    ></div>

    <aside
        x-show="sidebarOpen"
        x-transition:enter="transform ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 start-0 flex w-72 flex-col bg-white shadow-xl"
    >
        @include('layouts.partials.sidebar-nav', ['navSites' => $navSites, 'currentSite' => $currentSite])
    </aside>
</div>
