@php
    $platforms = $this->socialPlatformsConfig;
    $selected  = $platforms[$addingPlatform] ?? null;
    $canAddMore = $isDynamic || count($socialNetworks) === 0;
    $showHubBadge = $isDynamic && count($socialNetworks) > 1;
    $showDirectBadge = count($socialNetworks) === 1;
@endphp

<div class="space-y-5">
    {{-- Static / Dynamic helper copy --}}
    @if($isDynamic)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30 p-3">
            <p class="text-sm text-emerald-900 dark:text-emerald-100">
                <span class="font-semibold">{{ __('qr.social_dynamic_title') }}</span>
                {{ __('qr.social_dynamic_help') }}
            </p>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/60 p-3">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-semibold">{{ __('qr.social_static_title') }}</span>
                {{ __('qr.social_static_help') }}
            </p>
        </div>
    @endif

    {{-- Scan behaviour badge --}}
    @if($showHubBadge)
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-950/50 dark:text-primary-300">
                <i class="fa-solid fa-layer-group"></i>
                {{ __('qr.social_hub_badge') }}
            </span>
        </div>
    @elseif($showDirectBadge)
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                {{ __('qr.social_direct_badge') }}
            </span>
        </div>
    @endif

    {{-- Confirmed networks --}}
    @if(count($socialNetworks) > 0)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('qr.social_added_networks') }}
            </label>
            <ul class="space-y-2">
                @foreach($socialNetworks as $index => $network)
                    @php $meta = $platforms[$network['platform']] ?? $platforms['custom']; @endphp
                    <li class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 px-3 py-2.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm {{ $meta['icon_color'] }}"
                              style="{{ $meta['style'] }}">
                            <i class="{{ $meta['icon'] }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $meta['label'] }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $network['identifier'] }}</p>
                        </div>
                        <button type="button" wire:click="removeSocialNetwork({{ $index }})"
                                class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 transition"
                                title="{{ __('qr.social_remove_network') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add network form --}}
    @if($canAddMore)
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-zinc-600 p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ count($socialNetworks) === 0 ? __('qr.social_select_network') : __('qr.social_add_another') }}
                </label>
                <div class="grid grid-cols-4 gap-2 sm:grid-cols-5 lg:grid-cols-6">
                    @foreach($platforms as $key => $platform)
                        @php $isSelected = $addingPlatform === $key; @endphp
                        <button wire:click="selectAddingPlatform('{{ $key }}')" type="button"
                            class="group flex flex-col items-center gap-1.5 rounded-xl border-2 p-2 text-center transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1
                                {{ $isSelected
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30 shadow-sm'
                                    : 'border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-gray-300 dark:hover:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700' }}">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg text-sm {{ $platform['icon_color'] }}"
                                  style="{{ $platform['style'] }}">
                                <i class="{{ $platform['icon'] }}"></i>
                            </span>
                            <span class="w-full truncate text-[10px] font-medium leading-tight
                                {{ $isSelected ? 'text-primary-700 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $platform['label'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
                @error('addingPlatform')
                    <p class="mt-1.5 text-sm text-red-600">{{ __('qr.social_platform_required') }}</p>
                @enderror
            </div>

            @if($addingPlatform && $selected)
                <div class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/60 p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md text-xs {{ $selected['icon_color'] }}"
                              style="{{ $selected['style'] }}">
                            <i class="{{ $selected['icon'] }}"></i>
                        </span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selected['label'] }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            @if($addingPlatform === 'custom')
                                {{ __('qr.social_custom_url') }}
                            @else
                                {{ __('qr.social_identifier_label') }}
                            @endif
                        </label>
                        <input
                            wire:model.live.debounce.500ms="addingIdentifier"
                            type="{{ $addingPlatform === 'custom' ? 'url' : 'text' }}"
                            placeholder="{{ $selected['placeholder'] }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        >
                        @error('addingIdentifier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($this->addingUrlPreview && $addingPlatform !== 'custom')
                        <p class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-arrow-up-right-from-square mt-0.5 shrink-0"></i>
                            <span class="break-all">{{ $this->addingUrlPreview }}</span>
                        </p>
                    @endif

                    <button type="button" wire:click="addSocialNetwork"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ count($socialNetworks) === 0 ? __('qr.social_add_network') : __('qr.social_add_another') }}
                    </button>
                </div>
            @endif
        </div>
    @elseif(!$isDynamic && count($socialNetworks) >= 1)
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('qr.social_static_limit') }}
        </p>
    @endif

    @error('socialNetworks')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    {{-- Static downgrade confirmation --}}
    @if($showStaticDowngradeWarning)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-xl ring-1 ring-gray-900/5 dark:ring-zinc-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('qr.social_static_downgrade_title') }}</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('qr.social_static_downgrade_message') }}</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelStaticDowngrade"
                            class="rounded-lg border border-gray-300 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" wire:click="confirmStaticDowngrade"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                        {{ __('qr.social_static_downgrade_confirm') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
