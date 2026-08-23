<x-app-layout>
    <x-slot name="title">Sites</x-slot>

    <x-page>
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">Sites</h1>
                <p class="mt-1 text-sm text-zinc-500">Properties you’re collecting analytics for.</p>
            </div>
            <a
                href="{{ route('sites.create') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-zinc-800"
            >
                <i class="bx bx-plus text-lg"></i>
                Add site
            </a>
        </div>

        <x-flash />

        @if ($sites->isEmpty())
            <x-card class="py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                    <i class="bx bx-plus-circle text-2xl"></i>
                </div>
                <h2 class="mt-4 text-base font-semibold text-zinc-900">No sites yet</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-zinc-500">
                    Add a domain to get a tracking snippet and start seeing visitors, pages, and referrers.
                </p>
                <a
                    href="{{ route('sites.create') }}"
                    class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-zinc-800"
                >
                    Add your first site
                </a>
            </x-card>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($sites as $site)
                    <a href="{{ route('sites.show', $site) }}" class="group block">
                        <x-card class="h-full transition group-hover:ring-zinc-300">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                        <i class="bx bx-globe text-xl"></i>
                                    </div>
                                    <h2 class="mt-4 truncate text-base font-semibold text-zinc-900">{{ $site->name }}</h2>
                                    <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $site->domain }}</p>
                                </div>
                                <i class="bx bx-right-arrow-alt text-xl text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-zinc-500"></i>
                            </div>
                            <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-4 text-xs text-zinc-400">
                                <span>{{ number_format($site->pageviews_today) }} views today</span>
                                <span>{{ $site->timezone }}</span>
                            </div>
                        </x-card>
                    </a>
                @endforeach
            </div>
        @endif
    </x-page>
</x-app-layout>
