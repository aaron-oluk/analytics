@props(['title', 'icon' => 'bx-bar-chart-alt-2', 'rows'])

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
                <li class="relative overflow-hidden rounded-lg">
                    <div
                        class="absolute inset-y-0 start-0 rounded-lg bg-teal-50"
                        style="width: {{ round(($row->visitors / $max) * 100) }}%"
                    ></div>
                    <div class="relative flex items-center justify-between gap-3 px-3 py-2 text-sm">
                        <span class="truncate font-medium text-zinc-700" title="{{ $row->value }}">{{ $row->value }}</span>
                        <span class="shrink-0 tabular-nums text-zinc-500">{{ number_format($row->visitors) }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
