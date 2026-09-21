@props(['current'])

@php
    $tabs = [
        'overview' => ['route' => 'admin.dashboard', 'label' => __('admin.overview'), 'icon' => 'fa-solid fa-gauge-high'],
        'users' => ['route' => 'admin.users', 'label' => __('admin.users'), 'icon' => 'fa-solid fa-users'],
        'revenue' => ['route' => 'admin.revenue', 'label' => __('admin.revenue'), 'icon' => 'fa-solid fa-sack-dollar'],
        'usage' => ['route' => 'admin.usage', 'label' => __('admin.usage'), 'icon' => 'fa-solid fa-chart-line'],
    ];
@endphp

<div class="border-b border-gray-200 dark:border-zinc-800">
    <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="{{ __('admin.title') }}">
        @foreach($tabs as $key => $tab)
            <a href="{{ route($tab['route']) }}" wire:navigate
               @class([
                   'flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition',
                   'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => $current === $key,
                   'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-zinc-600 dark:hover:text-gray-200' => $current !== $key,
               ])
               @if($current === $key) aria-current="page" @endif>
                <i class="{{ $tab['icon'] }} text-xs" aria-hidden="true"></i>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
