@props(['title', 'icon' => 'bx-bar-chart-alt-2', 'rows', 'filterUrl' => null, 'filterKey' => null])

@php
    $max = max(1, (int) collect($rows)->max('visitors'));
@endphp

<x-card>
    <div class="mb-4 flex items-center gap-2">
        <i class="bx {{ $icon }} text-lg text-zinc-400"></i>
        <h3 class="text-sm font-semibold text-zinc-900">{{ $title }}</h3>
    </div>

    @if (count($rows) === 0)
        <div class="flex flex-col items-center py-8 text-center">
            <i class="bx bx-minus-circle text-2xl text-zinc-300"></i>
            <p class="mt-2 text-sm text-zinc-400">No data in this range</p>
        </div>
    @else
        <ul class="space-y-1.5">
            @foreach ($rows as $row)
                @php
                    $label = $filterKey === 'country'
                        ? \App\Services\Analytics\CountryCode::name($row->value)
                        : $row->value;
                @endphp
                <li class="relative overflow-hidden rounded-lg">
                    <div
                        class="absolute inset-y-0 start-0 rounded-lg bg-teal-50"
                        style="width: {{ round(($row->visitors / $max) * 100) }}%"
                    ></div>
                    <div class="relative flex items-center justify-between gap-3 px-3 py-2 text-sm">
                        @if ($filterUrl && $filterKey)
                            <a href="{{ $filterUrl([$filterKey => $row->value]) }}" class="truncate font-medium text-zinc-700 hover:text-teal-800" title="Filter by {{ $label }}">
                                {{ $label }}
                            </a>
                        @else
                            <span class="truncate font-medium text-zinc-700" title="{{ $label }}">{{ $label }}</span>
                        @endif
                        <span class="shrink-0 tabular-nums text-zinc-500">{{ number_format($row->visitors) }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
