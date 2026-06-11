@props(['variant' => 'default'])

@php
    $buttonClasses = match ($variant) {
        'dark' => 'text-gray-400 hover:text-white hover:bg-white/10',
        default => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-zinc-800',
    };
@endphp

<div
    x-data="{
        ...exclusiveDropdownMixin('theme'),
        theme: localStorage.getItem('theme') || 'system',
        get displayTheme() {
            if (this.theme === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            return this.theme;
        },
        applyTheme(value) {
            var isDark = value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            var meta = document.querySelector('meta[name=theme-color]');
            if (meta) {
                meta.content = isDark ? '#18181b' : '#ffffff';
            }
        },
        setTheme(value) {
            this.theme = value;
            try {
                localStorage.setItem('theme', value);
            } catch (e) {}
            this.applyTheme(value);
            this.closeDropdown();
        },
        init() {
            this._dropdownInit();
            this.applyTheme(this.theme);
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme('system');
                }
            });
        }
    }"
    @click.outside="closeDropdown()"
    class="relative flex-shrink-0"
>
    <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full transition-colors lg:h-10 lg:w-10 {{ $buttonClasses }}"
        title="{{ __('common.theme') }}"
        aria-label="{{ __('common.theme') }}"
        aria-haspopup="listbox"
        :aria-expanded="open"
        @click.stop="toggleDropdown()"
    >
        <svg x-show="theme === 'light'" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5 lg:h-6 lg:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <svg x-show="theme === 'system'" x-cloak class="h-5 w-5 lg:h-6 lg:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 top-full z-50 mt-2 w-40 rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
        role="listbox"
        @click.stop
    >
        @foreach ([
            ['value' => 'light', 'label' => __('common.theme_light'), 'icon' => 'sun', 'iconClass' => 'text-amber-500'],
            ['value' => 'dark', 'label' => __('common.theme_dark'), 'icon' => 'moon', 'iconClass' => 'text-indigo-400'],
            ['value' => 'system', 'label' => __('common.theme_system'), 'icon' => 'monitor', 'iconClass' => 'text-gray-500 dark:text-gray-400'],
        ] as $option)
            <button
                type="button"
                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800"
                :class="{ 'bg-gray-50 dark:bg-zinc-800/60': theme === '{{ $option['value'] }}' }"
                role="option"
                @click="setTheme('{{ $option['value'] }}')"
            >
                @if ($option['icon'] === 'sun')
                    <svg class="mr-3 h-4 w-4 {{ $option['iconClass'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                @elseif ($option['icon'] === 'moon')
                    <svg class="mr-3 h-4 w-4 {{ $option['iconClass'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                @else
                    <svg class="mr-3 h-4 w-4 {{ $option['iconClass'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                @endif
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>
</div>
