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

    $periods = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => 'Last 7 days',
        '14d' => 'Last 14 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'last_month' => 'Last month',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'custom' => 'Custom',
    ];

    $segments = [
        'path' => ['label' => 'Page', 'empty' => 'All pages'],
        'referrer' => ['label' => 'Source', 'empty' => 'All sources'],
        'device' => ['label' => 'Device', 'empty' => 'All devices'],
        'country' => ['label' => 'Country', 'empty' => 'All countries'],
        'browser' => ['label' => 'Browser', 'empty' => 'All browsers'],
        'utm_source' => ['label' => 'UTM source', 'empty' => 'All campaigns'],
    ];
@endphp

<x-card padding="p-4 sm:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Period</div>
            <p class="mt-0.5 text-sm text-zinc-600">{{ $rangeLabel }} · {{ $from->toFormattedDateString() }} – {{ $to->toFormattedDateString() }}</p>
        </div>
        @if (count($filters) > 0)
            <a href="{{ $filterUrl(['path' => '', 'referrer' => '', 'device' => '', 'country' => '', 'browser' => '', 'utm_source' => '']) }}" class="text-xs font-medium text-teal-700 hover:text-teal-800">
                Clear traffic filters
            </a>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach ($periods as $value => $label)
            <a
                href="{{ $filterUrl(['range' => $value]) }}"
                class="rounded-lg px-2.5 py-1 text-xs font-medium ring-1 transition {{ $range === $value ? 'bg-zinc-900 text-white ring-zinc-900' : 'bg-white text-zinc-600 ring-zinc-200 hover:text-zinc-900' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($range === 'custom')
        <form method="GET" action="{{ route('sites.show', $site) }}" class="mt-3 flex flex-wrap items-end gap-3">
            <input type="hidden" name="range" value="custom">
            @foreach ($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div>
                <label for="from" class="block text-xs font-medium text-zinc-500">From</label>
                <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 rounded-lg border-zinc-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-zinc-500">To</label>
                <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 rounded-lg border-zinc-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600" required>
            </div>
            <button type="submit" class="inline-flex items-center rounded-lg bg-zinc-900 px-3 py-2 text-xs font-medium text-white hover:bg-zinc-800">
                Apply dates
            </button>
        </form>
    @endif

    <div class="mt-5 border-t border-zinc-100 pt-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Traffic</div>
        <p class="mt-0.5 text-sm text-zinc-500">Slice the report by page, source, device, country, browser, or campaign.</p>

        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($segments as $key => $meta)
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ $meta['label'] }}</span>
                    <select
                        class="w-full rounded-lg border-zinc-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        onchange="window.location = this.value"
                    >
                        <option value="{{ $filterUrl([$key => '']) }}" @selected(! isset($filters[$key]))>{{ $meta['empty'] }}</option>
                        @foreach ($filterOptions[$key] as $option)
                            <option value="{{ $filterUrl([$key => $option->value]) }}" @selected(($filters[$key] ?? null) === $option->value)>
                                {{ $option->value }} ({{ number_format($option->visitors) }})
                            </option>
                        @endforeach
                    </select>
                </label>
            @endforeach
        </div>
    </div>
</x-card>
