<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-8 lg:flex-row">
        {{-- Design controls (left) --}}
        <div class="flex-1 space-y-6">
        @include('livewire.qr-codes._design-colors')

        {{-- Shape customization (Body, Eye Frame, Eye Ball) --}}
        <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-slate-50/80 p-3 sm:p-4">
            <div class="space-y-4">
                {{-- Body Shape (data modules) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Body Shape</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-0.5">Shape of the data modules</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach(config('qr_shapes.body') as $style => $config)
                        <button wire:click="setDesign('dotStyle', '{{ $style }}')" type="button"
                                title="{{ $config['label'] }}"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white dark:bg-zinc-900 shadow-sm transition-colors hover:border-gray-300 dark:border-zinc-700 {{ $dotStyle === $style ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/50 ring-1 ring-primary-200 dark:ring-primary-800' : 'border-gray-200 dark:border-zinc-800' }}">
                            <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Eye Frame Shape (outer corner squares) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Eye Frame Shape</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-0.5">Outer shape of the corner finder patterns</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach(config('qr_shapes.eye_frame') as $style => $config)
                        <button wire:click="setDesign('eyeFrameStyle', '{{ $style }}')" type="button"
                                title="{{ $config['label'] }}"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white dark:bg-zinc-900 shadow-sm transition-colors hover:border-gray-300 dark:border-zinc-700 {{ $eyeFrameStyle === $style ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/50 ring-1 ring-primary-200 dark:ring-primary-800' : 'border-gray-200 dark:border-zinc-800' }}">
                            <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Eye Ball Shape (inner dot of corners) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Eye Ball Shape</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-0.5">Inner shape of the corner finder patterns</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach(config('qr_shapes.eye_ball') as $style => $config)
                        <button wire:click="setDesign('eyeBallStyle', '{{ $style }}')" type="button"
                                title="{{ $config['label'] }}"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white dark:bg-zinc-900 shadow-sm transition-colors hover:border-gray-300 dark:border-zinc-700 {{ $eyeBallStyle === $style ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/50 ring-1 ring-primary-200 dark:ring-primary-800' : 'border-gray-200 dark:border-zinc-800' }}">
                            <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Frame --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frame</label>
            <div class="mt-2 flex flex-wrap gap-2">
            @foreach(['' => 'None', 'simple' => 'Simple Border', 'rounded' => 'Rounded', 'banner' => 'Banner'] as $style => $label)
            <button wire:click="setDesign('frameStyle', '{{ $style }}')" type="button"
                    class="rounded-lg border-2 px-3 py-1.5 text-xs {{ $frameStyle === $style ? 'border-primary-600 bg-primary-50 dark:bg-primary-950/50' : 'border-gray-200 dark:border-zinc-800' }}">
                {{ $label }}
            </button>
            @endforeach
            </div>
            @if($frameStyle)
            <div class="mt-3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 dark:text-gray-500">Call-to-action text</label>
                <input wire:model.live.debounce.300ms="frameText" type="text" placeholder="Scan me!" maxlength="30"
                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            @endif
        </div>

        <div x-data="{ iconModalOpen: false }">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.logo') }}</label>
            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mb-2">{{ __('qr.logo_help') }}</p>
            <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/svg+xml"
                   class="block w-full text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 dark:bg-primary-950/50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-primary-400 hover:file:bg-primary-100">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @if($existingLogo && !str_starts_with($existingLogo ?? '', 'icons/'))
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Current: {{ basename($existingLogo) }}</p>
            @endif

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <button type="button"
                        class="rounded-lg border border-gray-300 dark:border-zinc-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800"
                        @click="iconModalOpen = true">
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
                    <span class="text-xs text-primary-600">{{ $selectedIcon }}</span>
                    <button wire:click="selectIcon(null)" type="button" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:text-gray-300">Clear</button>
                @endif
            </div>

            {{-- Icon picker modal --}}
            <div x-show="iconModalOpen" x-cloak
                 x-effect="document.body.style.overflow = iconModalOpen ? 'hidden' : ''"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="absolute inset-0 bg-black/50" @click="iconModalOpen = false"></div>
                <div class="relative w-full max-w-2xl rounded-xl bg-white dark:bg-zinc-900 shadow-xl flex flex-col overflow-hidden"
                     @click.stop>
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-200 dark:border-zinc-800 px-4 py-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Choose an icon</h3>
                        <button type="button" @click="iconModalOpen = false"
                                class="rounded-lg p-1 text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 hover:text-gray-600 dark:text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                    <div class="min-h-0 max-h-[400px] overflow-y-auto overflow-x-hidden p-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->availableIcons as $icon)
                                <button type="button"
                                        wire:click="selectIcon('{{ $icon }}')"
                                        @click="iconModalOpen = false"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors {{ $selectedIcon === $icon ? 'bg-primary-50 dark:bg-primary-950/50' : 'hover:bg-gray-100 dark:hover:bg-zinc-800' }}">
                                    <img src="{{ asset('icons/qr-center-icons/' . $icon . '.svg') }}" alt="{{ $icon }}" class="h-4 w-4" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        {{-- Live preview (right) --}}
        <div class="lg:w-72 lg:shrink-0">
            <div class="lg:sticky lg:top-24">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Preview</p>
            <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm">
                <div class="relative" style="min-height: 200px">
                    @if($preview)
                        <img src="{{ $preview }}" alt="QR Code Preview"
                             class="w-full rounded-lg transition-opacity duration-200"
                             wire:loading.class="opacity-20"
                             wire:target="setDesign,applyColorPreset,setForegroundMode,fgColor,bgColor,useGradient,gradientColor1,gradientColor2,gradientType,logo,selectIcon,logoMatchFgColor,eyeFrameStyle,eyeBallStyle">
                    @else
                        <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-zinc-700" style="height: 200px">
                            <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-300"></i>
                        </div>
                    @endif
                    <div wire:loading.flex wire:target="setDesign,applyColorPreset,setForegroundMode,fgColor,bgColor,useGradient,gradientColor1,gradientColor2,gradientType,logo,selectIcon,logoMatchFgColor,eyeFrameStyle,eyeBallStyle"
                         class="absolute top-0 left-0 right-0 bottom-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-900/50" style="display:none">
                        <div class="flex flex-col items-center gap-3">
                            <i class="fa-solid fa-spinner fa-spin text-4xl text-primary-600"></i>
                            <span class="text-sm font-semibold text-primary-600">Updating...</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-center gap-1.5">
                    <div class="h-3 w-3 rounded-full border" style="background-color: {{ $fgColor }}"></div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">on</span>
                    <div class="h-3 w-3 rounded-full border" style="background-color: {{ $bgColor }}"></div>
                    <span class="mx-1 text-gray-300">|</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ $dotStyle }}</span>
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- Navigation buttons (below both columns) --}}
    <div class="flex w-full justify-between border-t border-gray-200 dark:border-zinc-800 pt-6">
        <button wire:click="previousStep" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800/60 transition">
            {{ __('common.back') }}
        </button>
        <button wire:click="nextStep" class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
            {{ __('common.next') }}
        </button>
    </div>
</div>
