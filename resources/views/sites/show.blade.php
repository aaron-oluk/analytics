<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $site->name }}</h2>
                <p class="text-sm text-gray-500">{{ $site->domain }}</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    {{ $realtimeVisitors }} visitor{{ $realtimeVisitors === 1 ? '' : 's' }} right now
                </span>
                <a href="{{ route('sites.edit', $site) }}" class="text-sm text-gray-500 hover:text-gray-800">Settings</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-700 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <details class="bg-white shadow-sm sm:rounded-lg p-6" {{ $overview['pageviews'] === 0 ? 'open' : '' }}>
                <summary class="cursor-pointer font-semibold text-gray-800">Tracking snippet</summary>
                <p class="text-sm text-gray-500 mt-2">Paste this right before the closing <code>&lt;/body&gt;</code> tag of every page you want to track. No cookies, no consent banner required.</p>
                <pre class="mt-3 bg-gray-900 text-gray-100 text-xs p-4 rounded-md overflow-x-auto"><code>&lt;script defer src="{{ url('/tracker.js') }}" data-site="{{ $site->domain }}"&gt;&lt;/script&gt;</code></pre>
            </details>

            <div class="flex gap-2">
                @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days', '90d' => '90 days'] as $value => $label)
                    <a href="{{ route('sites.show', [$site, 'range' => $value]) }}"
                       class="px-3 py-1.5 text-sm rounded-md {{ $range === $value ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ([
                    'Visitors' => number_format($overview['visitors']),
                    'Pageviews' => number_format($overview['pageviews']),
                    'Bounce rate' => $overview['bounce_rate'].'%',
                    'Avg. duration' => gmdate('i:s', $overview['avg_duration_seconds']),
                ] as $label => $value)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <canvas id="visitorsChart" height="80"></canvas>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                @foreach ([
                    'Top pages' => $topPages,
                    'Top referrers' => $topReferrers,
                    'Countries' => $topCountries,
                    'Devices' => $topDevices,
                    'Browsers' => $topBrowsers,
                ] as $title => $rows)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-3">{{ $title }}</h3>
                        @if (count($rows) === 0)
                            <p class="text-sm text-gray-400">No data yet.</p>
                        @else
                            <table class="w-full text-sm">
                                @foreach ($rows as $row)
                                    <tr class="border-t">
                                        <td class="py-2 text-gray-700 truncate max-w-xs">{{ $row->value }}</td>
                                        <td class="py-2 text-right text-gray-500">{{ number_format($row->visitors) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        new Chart(document.getElementById('visitorsChart'), {
            type: 'line',
            data: {
                labels: @json(array_keys($timeseries)),
                datasets: [{
                    label: 'Visitors',
                    data: @json(array_values($timeseries)),
                    borderColor: '#1f2937',
                    backgroundColor: 'rgba(31,41,55,0.08)',
                    tension: 0.3,
                    fill: true,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    </script>
</x-app-layout>
