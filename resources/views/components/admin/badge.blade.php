@props(['tone' => 'gray'])

@php
    $tones = [
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-gray-300',
        'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400',
        'blue' => 'bg-primary-100 text-primary-700 dark:bg-primary-950/60 dark:text-primary-400',
        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ' . ($tones[$tone] ?? $tones['gray'])]) }}>
    {{ $slot }}
</span>
