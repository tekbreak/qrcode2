@props([
    'rows',            // iterable of ['label' => string, 'value' => int]
    'color' => 'bg-primary-500',
    'money' => false,
])

@php
    $items = collect($rows)->values();
    $total = max(1, (int) $items->sum('value'));
    $fmt = fn ($v) => $money ? \App\Support\Money::format($v) : number_format($v);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @forelse($items as $item)
        @php $pct = round(($item['value'] / $total) * 100); @endphp
        <div>
            <div class="flex items-baseline justify-between gap-2 text-sm">
                <span class="truncate text-gray-600 dark:text-gray-300" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                <span class="shrink-0 tabular-nums text-gray-900 dark:text-gray-100">
                    {{ $fmt($item['value']) }}
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $pct }}%</span>
                </span>
            </div>
            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-zinc-800">
                <div class="h-1.5 rounded-full {{ $color }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    @empty
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.no_data') }}</p>
    @endforelse
</div>
