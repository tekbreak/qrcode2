@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'default',
    'href' => null,
])

@php
    $tones = [
        'default' => 'text-gray-900 dark:text-gray-100',
        'positive' => 'text-emerald-600 dark:text-emerald-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'danger' => 'text-red-600 dark:text-red-400',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'block rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 transition dark:bg-zinc-900 dark:ring-zinc-800' . ($href ? ' hover:ring-primary-300 dark:hover:ring-primary-700' : '')]) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
        @if($icon)
            <i class="{{ $icon }} text-sm text-gray-300 dark:text-zinc-600" aria-hidden="true"></i>
        @endif
    </div>
    <p class="mt-1 text-2xl font-bold tabular-nums {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
