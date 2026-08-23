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
    $snippet = '<script defer src="'.url('/tracker.js').'" data-site="'.$site->domain.'"></script>';
@endphp

<x-app-layout>
    <x-slot name="title">{{ $site->name }}</x-slot>

    <x-page>
        <x-flash />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="truncate text-2xl font-semibold tracking-tight text-zinc-900">{{ $site->name }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200">
                        <span class="live-dot h-1.5 w-1.5 rounded-full {{ $realtimeVisitors > 0 ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                        {{ $realtimeVisitors }} live
                    </span>
                </div>
                <p class="mt-1 truncate text-sm text-zinc-500">{{ $site->domain }} · {{ $rangeLabel }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ $exportUrl }}"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3 text-sm font-medium text-zinc-600 ring-1 ring-zinc-200 transition hover:text-zinc-900"
                >
                    <i class="bx bx-download text-lg"></i>
                    Export
                </a>
                <a
                    href="{{ route('sites.edit', $site) }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-zinc-500 ring-1 ring-zinc-200 transition hover:text-zinc-900"
                    title="Settings"
                >
                    <i class="bx bx-cog text-lg"></i>
                </a>
            </div>
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

        <details class="group" {{ $overview['pageviews'] === 0 ? 'open' : '' }}>
            <x-card padding="px-5 py-4">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                    <span class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                        <i class="bx bx-code-alt text-lg text-zinc-400"></i>
                        Tracking snippet
                    </span>
                    <i class="bx bx-chevron-down text-xl text-zinc-400 transition group-open:rotate-180"></i>
                </summary>
                <p class="mt-3 text-sm text-zinc-500">
                    Paste this right before the closing <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs text-zinc-700">&lt;/body&gt;</code>
                    tag. No cookies, no consent banner.
                </p>
                <div
                    x-data="{ copied: false, snippet: {{ \Illuminate\Support\Js::from($snippet) }} }"
                    class="mt-3 flex items-start gap-2"
                >
                    <pre class="min-w-0 flex-1 overflow-x-auto rounded-lg bg-zinc-950 px-4 py-3 text-xs leading-relaxed text-zinc-100"><code x-text="snippet"></code></pre>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText(snippet); copied = true; setTimeout(() => copied = false, 1600)"
                        class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-zinc-900 px-3 text-xs font-medium text-white hover:bg-zinc-800"
                    >
                        <i class="bx text-base" :class="copied ? 'bx-check' : 'bx-copy'"></i>
                        <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                </div>
            </x-card>
        </details>

        <x-card>
            <div class="grid grid-cols-2 gap-6 md:grid-cols-4 md:divide-x md:divide-zinc-100">
                <x-stat class="md:pe-6" label="Visitors" :value="number_format($overview['visitors'])" icon="bx-user" />
                <x-stat class="md:px-6" label="Pageviews" :value="number_format($overview['pageviews'])" icon="bx-show" />
                <x-stat class="md:px-6" label="Bounce rate" :value="$overview['bounce_rate'].'%'" icon="bx-exit" />
                <x-stat class="md:ps-6" label="Avg. duration" :value="gmdate('i:s', $overview['avg_duration_seconds'])" icon="bx-time-five" />
            </div>
        </x-card>

        <x-card>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Visitors</h2>
                <span class="text-xs text-zinc-400">{{ $rangeLabel }}</span>
            </div>
            <canvas id="visitorsChart" height="88"></canvas>
        </x-card>

        <div class="grid gap-6 md:grid-cols-2">
            <x-breakdown title="Top pages" icon="bx-file" :rows="$topPages" :filter-url="$filterUrl" filter-key="path" />
            <x-breakdown title="Top referrers" icon="bx-link" :rows="$topReferrers" :filter-url="$filterUrl" filter-key="referrer" />
            <x-breakdown title="Countries" icon="bx-globe" :rows="$topCountries" :filter-url="$filterUrl" filter-key="country" />
            <div class="grid gap-6">
                <x-breakdown title="Devices" icon="bx-devices" :rows="$topDevices" :filter-url="$filterUrl" filter-key="device" />
                <x-breakdown title="Browsers" icon="bx-window" :rows="$topBrowsers" :filter-url="$filterUrl" filter-key="browser" />
            </div>
        </div>
    </x-page>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const labels = @json(array_keys($timeseries));
        const values = @json(array_values($timeseries));

        new Chart(document.getElementById('visitorsChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Visitors',
                    data: values,
                    borderColor: '#0d9488',
                    backgroundColor: (context) => {
                        const { ctx, chartArea } = context.chart;
                        if (!chartArea) return 'rgba(13, 148, 136, 0.08)';
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(13, 148, 136, 0.22)');
                        gradient.addColorStop(1, 'rgba(13, 148, 136, 0)');
                        return gradient;
                    },
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#0d9488',
                }],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#18181b',
                        titleColor: '#a1a1aa',
                        bodyColor: '#fff',
                        padding: 10,
                        displayColors: false,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#a1a1aa', maxRotation: 0, autoSkipPadding: 16 },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#a1a1aa' },
                        grid: { color: 'rgba(24, 24, 27, 0.06)' },
                        border: { display: false },
                    },
                },
            },
        });
    </script>
</x-app-layout>
