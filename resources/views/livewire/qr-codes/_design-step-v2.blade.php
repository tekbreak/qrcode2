@php
    $colorPresets = [
        ['label' => 'Classic', 'fg' => '#000000', 'bg' => '#FFFFFF', 'gradient' => false],
        ['label' => 'Ocean', 'fg' => '#0369a1', 'bg' => '#f0f9ff', 'gradient' => false],
        ['label' => 'Forest', 'fg' => '#166534', 'bg' => '#f0fdf4', 'gradient' => false],
        ['label' => 'Sunset', 'fg' => '#c2410c', 'bg' => '#fff7ed', 'gradient' => false],
        ['label' => 'Berry', 'fg' => '#9333ea', 'bg' => '#faf5ff', 'gradient' => false],
        ['label' => 'Midnight', 'fg' => '#e2e8f0', 'bg' => '#0f172a', 'gradient' => false],
        ['label' => 'Aurora', 'fg' => '#6366f1', 'bg' => '#FFFFFF', 'gradient' => true, 'g1' => '#6366f1', 'g2' => '#ec4899'],
        ['label' => 'Fire', 'fg' => '#dc2626', 'bg' => '#FFFFFF', 'gradient' => true, 'g1' => '#f97316', 'g2' => '#dc2626'],
    ];

    $previewTargets = 'setDesign,applyColorPreset,setForegroundMode,fgColor,bgColor,useGradient,gradientColor1,gradientColor2,gradientType,logo,selectIcon,logoMatchFgColor,dotStyle,eyeFrameStyle,eyeBallStyle,frameStyle,frameText';
@endphp

<div class="flex flex-col gap-8" x-data="{ openSection: 'colors' }">
    <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
        {{-- Design controls --}}
        <div class="min-w-0 flex-1 space-y-4">
            <div class="rounded-lg border border-primary-200/70 bg-primary-50/50 px-4 py-3 dark:border-primary-900/50 dark:bg-primary-950/30">
                <p class="text-sm font-medium text-primary-800 dark:text-primary-200">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>
                    Design Studio
                </p>
                <p class="mt-0.5 text-xs text-primary-700/80 dark:text-primary-300/80">Vector-quality preview with crisp gradients and shapes.</p>
            </div>

            {{-- Colors --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800">
                <button type="button" @click="openSection = openSection === 'colors' ? null : 'colors'"
                        class="flex w-full items-center justify-between bg-white px-4 py-3 text-left transition hover:bg-gray-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/80">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Colors & Gradient</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'colors' && 'rotate-180'"></i>
                </button>
                <div x-show="openSection === 'colors'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="border-t border-gray-200 bg-slate-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Quick palettes</p>
                    <div class="mb-5 flex flex-wrap gap-2">
                        @foreach($colorPresets as $preset)
                            <button type="button"
                                    @if($preset['gradient'])
                                        wire:click="applyColorPreset('{{ $preset['fg'] }}', '{{ $preset['bg'] }}', '{{ $preset['g1'] }}', '{{ $preset['g2'] }}', true)"
                                    @else
                                        wire:click="applyColorPreset('{{ $preset['fg'] }}', '{{ $preset['bg'] }}', null, null, false)"
                                    @endif
                                    title="{{ $preset['label'] }}"
                                    class="inline-flex w-[4.5rem] shrink-0 flex-col items-center gap-1.5 rounded-lg border border-gray-200 bg-white p-2 transition hover:border-primary-400 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-primary-500">
                                <span class="relative flex h-9 w-9 overflow-hidden rounded-md border border-black/10 shadow-inner dark:border-white/10"
                                      style="background-color: {{ $preset['bg'] }};">
                                    @if($preset['gradient'])
                                        <span class="absolute inset-0" style="background: linear-gradient(135deg, {{ $preset['g1'] }}, {{ $preset['g2'] }});"></span>
                                    @else
                                        <span class="absolute bottom-0.5 right-0.5 h-3.5 w-3.5 rounded-sm border border-white/80 shadow-sm dark:border-black/20"
                                              style="background-color: {{ $preset['fg'] }};"></span>
                                    @endif
                                </span>
                                <span class="w-full truncate text-center text-[10px] font-medium leading-tight text-gray-600 dark:text-gray-400">{{ $preset['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    @include('livewire.qr-codes._design-colors', ['showDivider' => true])
                </div>
            </div>

            {{-- Shapes --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800">
                <button type="button" @click="openSection = openSection === 'shapes' ? null : 'shapes'"
                        class="flex w-full items-center justify-between bg-white px-4 py-3 text-left transition hover:bg-gray-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/80">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Shapes</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'shapes' && 'rotate-180'"></i>
                </button>
                <div x-show="openSection === 'shapes'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="space-y-5 border-t border-gray-200 bg-slate-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
                    @foreach([
                        'body' => ['label' => 'Body Shape', 'hint' => 'Shape of the data modules', 'property' => 'dotStyle', 'selected' => $dotStyle, 'prefix' => 'body', 'shapes' => config('qr_shapes.body')],
                        'eye_frame' => ['label' => 'Eye Frame Shape', 'hint' => 'Outer corner finder pattern', 'property' => 'eyeFrameStyle', 'selected' => $eyeFrameStyle, 'prefix' => 'frame', 'shapes' => config('qr_shapes.eye_frame')],
                        'eye_ball' => ['label' => 'Eye Ball Shape', 'hint' => 'Inner corner finder pattern', 'property' => 'eyeBallStyle', 'selected' => $eyeBallStyle, 'prefix' => 'ball', 'shapes' => config('qr_shapes.eye_ball')],
                    ] as $group)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $group['label'] }}</label>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $group['hint'] }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($group['shapes'] as $style => $config)
                                    @php
                                        $thumb = "qr-shape-previews/{$group['prefix']}_{$style}.png";
                                        $hasThumb = file_exists(public_path($thumb));
                                    @endphp
                                    <button wire:click="setDesign('{{ $group['property'] }}', '{{ $style }}')" type="button"
                                            title="{{ $config['label'] }}"
                                            class="inline-flex w-[3.75rem] shrink-0 flex-col items-center gap-1 rounded-lg border p-1.5 transition {{ $group['selected'] === $style ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-200 dark:bg-primary-950/40 dark:ring-primary-800' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}">
                                        <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded bg-white dark:bg-zinc-800">
                                            @if($hasThumb)
                                                <img src="{{ asset($thumb) }}" alt="{{ $config['label'] }}" class="h-full w-full object-cover object-left-top" loading="lazy">
                                            @else
                                                <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="18" />
                                            @endif
                                        </span>
                                        <span class="w-full truncate text-center text-[9px] leading-tight text-gray-500 dark:text-gray-400">{{ $config['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Frame --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800">
                <button type="button" @click="openSection = openSection === 'frame' ? null : 'frame'"
                        class="flex w-full items-center justify-between bg-white px-4 py-3 text-left transition hover:bg-gray-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/80">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Frame</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'frame' && 'rotate-180'"></i>
                </button>
                <div x-show="openSection === 'frame'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="border-t border-gray-200 bg-slate-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
                    <div class="flex flex-wrap gap-2">
                        @foreach(['' => 'None', 'simple' => 'Simple Border', 'rounded' => 'Rounded', 'banner' => 'Banner'] as $style => $label)
                            <button wire:click="setDesign('frameStyle', '{{ $style }}')" type="button"
                                    class="inline-flex shrink-0 rounded-lg border-2 px-3 py-1.5 text-xs font-medium transition {{ $frameStyle === $style ? 'border-primary-600 bg-primary-50 text-primary-800 dark:bg-primary-950/50 dark:text-primary-200' : 'border-gray-200 text-gray-700 hover:border-gray-300 dark:border-zinc-700 dark:text-gray-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @if($frameStyle)
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Call-to-action text</label>
                            <input wire:model.live.debounce.500ms="frameText" type="text" placeholder="Scan me!" maxlength="30"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-zinc-700">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Logo --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800" x-data="{ iconModalOpen: false }">
                <button type="button" @click="openSection = openSection === 'logo' ? null : 'logo'"
                        class="flex w-full items-center justify-between bg-white px-4 py-3 text-left transition hover:bg-gray-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/80">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('qr.logo') }}</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'logo' && 'rotate-180'"></i>
                </button>
                <div x-show="openSection === 'logo'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="border-t border-gray-200 bg-slate-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ __('qr.logo_help') }}</p>
                    <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white p-4 dark:border-zinc-600 dark:bg-zinc-900">
                        <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/svg+xml"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 dark:text-gray-400 dark:file:bg-primary-950/50 dark:file:text-primary-400">
                        @error('logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @if($existingLogo && !str_starts_with($existingLogo ?? '', 'icons/'))
                        <p class="mt-2 text-xs text-gray-500">Current: {{ basename($existingLogo) }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" @click="iconModalOpen = true"
                                class="inline-flex shrink-0 items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-800">
                            <i class="fa-solid fa-icons mr-1.5"></i>Use an icon
                        </button>
                        <label @class([
                            'inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400',
                            'opacity-50 cursor-not-allowed' => ! $selectedIcon,
                        ])>
                            <input wire:model.live="logoMatchFgColor" type="checkbox"
                                   @disabled(! $selectedIcon)
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <span>{{ __('qr.logo_match_fg_color') }}</span>
                        </label>
                        @if($selectedIcon)
                            <img src="{{ asset('icons/qr-center-icons/' . $selectedIcon . '.svg') }}" alt="" class="h-8 w-8 rounded border border-gray-200 p-1 dark:border-zinc-700">
                            <span class="text-xs text-primary-600 dark:text-primary-400">{{ $selectedIcon }}</span>
                            <button wire:click="selectIcon(null)" type="button" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</button>
                        @endif
                    </div>

                    <div x-show="iconModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50" @click="iconModalOpen = false"></div>
                        <div class="relative max-h-[80vh] w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-zinc-900" @click.stop>
                            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-zinc-800">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Choose an icon</h3>
                                <button type="button" @click="iconModalOpen = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-zinc-800"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <div class="max-h-[400px] overflow-y-auto p-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($this->availableIcons as $icon)
                                        <button type="button" wire:click="selectIcon('{{ $icon }}')" @click="iconModalOpen = false"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $selectedIcon === $icon ? 'bg-primary-50 dark:bg-primary-950/50' : 'hover:bg-gray-100 dark:hover:bg-zinc-800' }}">
                                            <img src="{{ asset('icons/qr-center-icons/' . $icon . '.svg') }}" alt="{{ $icon }}" class="h-4 w-4" loading="lazy">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live preview --}}
        <div class="w-full lg:w-72 lg:shrink-0">
            <div class="lg:sticky lg:top-24">
                <p class="mb-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">Live Preview</p>
                <div @class([
                    'rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900',
                    'ring-2 ring-gray-900 ring-offset-2 dark:ring-gray-100' => $frameStyle === 'simple',
                    'rounded-2xl ring-2 ring-gray-900 ring-offset-2 dark:ring-gray-100' => $frameStyle === 'rounded',
                ])>
                    @if($frameStyle === 'banner')
                        <div class="mb-3 rounded-md px-3 py-2 text-center text-xs font-semibold text-white" style="background-color: {{ $useGradient ? $gradientColor1 : $fgColor }};">
                            {{ $frameText ?: 'Scan me!' }}
                        </div>
                    @endif
                    <div class="relative min-h-[200px]">
                        @if($preview)
                            <img src="{{ $preview }}" alt="QR Code Preview"
                                 class="mx-auto w-full max-w-[220px] rounded-lg transition-opacity duration-200"
                                 wire:loading.class="opacity-20"
                                 wire:target="{{ $previewTargets }}">
                        @else
                            <div class="flex h-[200px] items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-zinc-700">
                                <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-300"></i>
                            </div>
                        @endif
                        <div wire:loading.flex wire:target="{{ $previewTargets }}"
                             class="absolute inset-0 items-center justify-center rounded-lg bg-white/70 dark:bg-zinc-900/70" style="display:none">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin text-2xl text-primary-600"></i>
                                <span class="text-xs font-semibold text-primary-600">Updating...</span>
                            </div>
                        </div>
                    </div>
                    @if($frameStyle && $frameStyle !== 'banner')
                        <p class="mt-3 text-center text-xs font-medium text-gray-600 dark:text-gray-400">{{ $frameText ?: 'Scan me!' }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 px-2 py-0.5 dark:border-zinc-700">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $useGradient ? "linear-gradient(135deg, {$gradientColor1}, {$gradientColor2})" : $fgColor }}"></span>
                            fg
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 px-2 py-0.5 dark:border-zinc-700">
                            <span class="h-2.5 w-2.5 rounded-full border border-gray-300 dark:border-zinc-600" style="background-color: {{ $bgColor }}"></span>
                            bg
                        </span>
                        <span class="rounded-full border border-gray-200 px-2 py-0.5 capitalize dark:border-zinc-700">{{ str_replace('_', ' ', $dotStyle) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex w-full justify-between border-t border-gray-200 pt-6 dark:border-zinc-800">
        <button wire:click="previousStep" type="button"
                class="inline-flex shrink-0 rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-800">
            {{ __('common.back') }}
        </button>
        <button wire:click="nextStep" type="button"
                class="inline-flex shrink-0 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
            {{ __('common.next') }}
        </button>
    </div>
</div>
