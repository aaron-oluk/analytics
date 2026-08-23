@php
    $rangeLabel = match ($range) {
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '14d' => 'Last 14 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'last_month' => 'Last month',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'custom' => $from->toFormattedDateString().' – '.$to->toFormattedDateString(),
        default => 'Last 7 days',
    };

    $downloads = [
        ['key' => 'events', 'label' => 'Pageviews', 'help' => 'Every recorded hit in this range'],
        ['key' => 'daily', 'label' => 'Daily totals', 'help' => 'Visitors, pageviews, and sessions by day'],
        ['key' => 'pages', 'label' => 'Pages', 'help' => 'Top paths'],
        ['key' => 'referrers', 'label' => 'Referrers', 'help' => 'Traffic sources'],
        ['key' => 'countries', 'label' => 'Countries', 'help' => 'Country names and codes'],
        ['key' => 'devices', 'label' => 'Devices', 'help' => 'Desktop, mobile, tablet'],
        ['key' => 'browsers', 'label' => 'Browsers', 'help' => 'Chrome, Firefox, and others'],
        ['key' => 'os', 'label' => 'Operating systems', 'help' => 'macOS, Windows, Android'],
        ['key' => 'utm_source', 'label' => 'UTM sources', 'help' => 'Campaign sources'],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Export · {{ $site->name }}</x-slot>

    <x-page width="max-w-7xl">
        <x-flash />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm text-zinc-500">
                    <a href="{{ $dashboardUrl }}" class="font-medium text-teal-700 hover:text-teal-800">{{ $site->name }}</a>
                    <span class="text-zinc-300">/</span>
                    Export
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">Analyse and download</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ $site->domain }} · {{ $rangeLabel }} · {{ number_format($overview['pageviews']) }} pageviews</p>
            </div>
            <a
                href="{{ $dashboardUrl }}"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3 text-sm font-medium text-zinc-600 ring-1 ring-zinc-200 transition hover:text-zinc-900"
            >
                <i class="bx bx-line-chart text-lg"></i>
                Dashboard
            </a>
        </div>

        <x-traffic-filters
            :site="$site"
            :range="$range"
            :from="$from"
            :to="$to"
            :filters="$filters"
            :filter-url="$filterUrl"
            :filter-options="$filterOptions"
        />

        <x-card>
            <div class="grid grid-cols-2 gap-6 md:grid-cols-4 md:divide-x md:divide-zinc-100">
                <x-stat class="md:pe-6" label="Visitors" :value="number_format($overview['visitors'])" icon="bx-user" />
                <x-stat class="md:px-6" label="Pageviews" :value="number_format($overview['pageviews'])" icon="bx-show" />
                <x-stat class="md:px-6" label="Bounce rate" :value="$overview['bounce_rate'].'%'" icon="bx-exit" />
                <x-stat class="md:ps-6" label="Avg. duration" :value="gmdate('i:s', $overview['avg_duration_seconds'])" icon="bx-time-five" />
            </div>
        </x-card>

        <x-card>
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-zinc-900">Download CSV</h2>
                <p class="mt-0.5 text-sm text-zinc-500">Files use the period and filters above. Open them in Excel, Sheets, or any notebook.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($downloads as $download)
                    <a
                        href="{{ $downloadUrl($download['key']) }}"
                        class="flex items-start gap-3 rounded-xl bg-zinc-50 px-4 py-3 ring-1 ring-zinc-100 transition hover:bg-white hover:ring-zinc-200"
                    >
                        <i class="bx bx-file text-xl text-teal-700"></i>
                        <span>
                            <span class="block text-sm font-medium text-zinc-900">{{ $download['label'] }}</span>
                            <span class="mt-0.5 block text-xs text-zinc-500">{{ $download['help'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900">Pageviews</h2>
                    <p class="mt-0.5 text-sm text-zinc-500">Newest first. Download the full set from the cards above.</p>
                </div>
                <a
                    href="{{ $downloadUrl('events') }}"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-zinc-900 px-3 text-xs font-medium text-white hover:bg-zinc-800"
                >
                    <i class="bx bx-download text-base"></i>
                    Download pageviews
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-y border-zinc-100 bg-zinc-50 text-xs font-medium uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Time</th>
                            <th class="px-5 py-2.5 font-medium">Page</th>
                            <th class="px-5 py-2.5 font-medium">Source</th>
                            <th class="px-5 py-2.5 font-medium">Country</th>
                            <th class="px-5 py-2.5 font-medium">Device</th>
                            <th class="px-5 py-2.5 font-medium">Browser</th>
                            <th class="px-5 py-2.5 font-medium">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($events as $event)
                            <tr class="text-zinc-700">
                                <td class="whitespace-nowrap px-5 py-2.5 text-zinc-500">{{ $event->occurred_at?->timezone($site->timezone)->format('M j, Y H:i') }}</td>
                                <td class="max-w-[16rem] truncate px-5 py-2.5 font-medium" title="{{ $event->pathname }}">{{ $event->pathname }}</td>
                                <td class="max-w-[12rem] truncate px-5 py-2.5 text-zinc-500">{{ $event->referrer_domain ?: 'Direct' }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5">{{ \App\Services\Analytics\CountryCode::name($event->country_code) }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 capitalize text-zinc-500">{{ $event->device_type ?: '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-zinc-500">{{ $event->browser ?: '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 tabular-nums text-zinc-500">{{ $event->duration_seconds !== null ? gmdate('i:s', $event->duration_seconds) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-zinc-400">No pageviews in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <div class="border-t border-zinc-100 px-5 py-3">
                    {{ $events->links() }}
                </div>
            @endif
        </x-card>
    </x-page>
</x-app-layout>
