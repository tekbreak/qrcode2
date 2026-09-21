@props([
    'label' => null,
    'used',
    'limit' => null,   // null = unlimited
    'compact' => false,
])

@php
    $pct = $limit ? min(100, (int) round(($used / max(1, $limit)) * 100)) : null;
    $tone = match (true) {
        $pct === null => 'bg-gray-300 dark:bg-zinc-600',
        $pct >= 100 => 'bg-red-500',
        $pct >= 80 => 'bg-amber-500',
        default => 'bg-primary-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'min-w-[5rem]']) }}>
    <div class="flex items-baseline justify-between gap-2 {{ $compact ? 'text-xs' : 'text-sm' }}">
        @if($label)
            <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
        @endif
        <span class="font-medium tabular-nums text-gray-900 dark:text-gray-100">
            {{ number_format($used) }}<span class="text-gray-400 dark:text-gray-500">/{{ $limit === null ? '∞' : number_format($limit) }}</span>
        </span>
    </div>
    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-zinc-800">
        <div class="h-1.5 rounded-full {{ $tone }}" style="width: {{ $pct === null ? ($used > 0 ? 100 : 0) : $pct }}%"></div>
    </div>
</div>
