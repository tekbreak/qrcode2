@props([
    'series',           // iterable of ['label' => string, 'value' => int]
    'money' => false,   // render values as currency
    'color' => 'bg-primary-500',
    'height' => 'h-40',
    'labelEvery' => null,
    'label' => null,    // accessible description of the chart
])

@php
    $points = collect($series)->values();
    $max = max(1, (int) $points->max('value'));
    $total = (int) $points->sum('value');
    $count = max(1, $points->count());
    $labelEvery = $labelEvery ?? (int) ceil($count / 6);
    $fmt = fn ($v) => $money ? \App\Support\Money::format($v) : number_format($v);
@endphp

<div {{ $attributes }}>
    <div class="flex items-baseline justify-between text-xs text-gray-400 dark:text-gray-500">
        <span>{{ __('admin.total') }}: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $fmt($total) }}</span></span>
        <span>{{ __('admin.peak') }} {{ $fmt($max) }}</span>
    </div>

    @if($total === 0)
        <div class="{{ $height }} mt-3 flex items-center justify-center rounded-lg border border-dashed border-gray-200 text-xs text-gray-400 dark:border-zinc-800 dark:text-gray-500">
            {{ __('admin.no_data') }}
        </div>
    @else
        <div class="{{ $height }} mt-3 flex items-end gap-[2px]" role="img" aria-label="{{ $label }}">
            @foreach($points as $point)
                @php $pct = $max > 0 ? max(2, round(($point['value'] / $max) * 100)) : 2; @endphp
                <div class="group relative flex-1">
                    <div class="{{ $point['value'] > 0 ? $color : 'bg-gray-200 dark:bg-zinc-800' }} w-full rounded-t-[2px] transition hover:opacity-80"
                         style="height: {{ $pct }}%; min-height: 2px;"
                         title="{{ $point['label'] }}: {{ $fmt($point['value']) }}"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-[10px] text-gray-400 dark:text-gray-500">
            @foreach($points as $i => $point)
                @if($i % $labelEvery === 0 || $i === $points->count() - 1)
                    <span>{{ $point['label'] }}</span>
                @endif
            @endforeach
        </div>
    @endif
</div>
