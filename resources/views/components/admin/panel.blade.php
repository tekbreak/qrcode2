@props(['title' => null, 'subtitle' => null, 'padding' => 'p-5'])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 dark:bg-zinc-900 dark:ring-zinc-800']) }}>
    @if($title || isset($actions))
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
