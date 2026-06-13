{{-- Shared foreground / background / gradient controls --}}
@props(['showDivider' => false])

<div @class([
    'space-y-5',
    'mt-6 border-t border-gray-200 pt-6 dark:border-zinc-700' => $showDivider,
])>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.bg_color') }}</label>
        <div class="mt-2 flex items-center gap-2">
            <input wire:model.live.debounce.300ms="bgColor" type="color" class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 dark:border-zinc-700">
            <input wire:model.live.debounce.500ms="bgColor" type="text" class="block min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-zinc-700" maxlength="7">
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.fg_color') }}</label>
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800">
                <button type="button" wire:click="setForegroundMode('solid')"
                        class="rounded-md px-3 py-1 text-xs font-medium transition {{ ! $useGradient ? 'bg-white text-gray-900 shadow-sm dark:bg-zinc-900 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    Solid
                </button>
                <button type="button" wire:click="setForegroundMode('gradient')"
                        class="rounded-md px-3 py-1 text-xs font-medium transition {{ $useGradient ? 'bg-white text-gray-900 shadow-sm dark:bg-zinc-900 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    Gradient
                </button>
            </div>
        </div>

        @if(! $useGradient)
            <div class="mt-2 flex items-center gap-2">
                <input wire:model.live.debounce.300ms="fgColor" type="color" class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 dark:border-zinc-700">
                <input wire:model.live.debounce.500ms="fgColor" type="text" class="block min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-zinc-700" maxlength="7">
            </div>
        @else
            <div class="mt-3 h-3 w-full overflow-hidden rounded-full border border-gray-200 shadow-inner dark:border-zinc-700"
                 style="background: {{ $gradientType === 'radial' ? "radial-gradient(circle, {$gradientColor1}, {$gradientColor2})" : "linear-gradient(90deg, {$gradientColor1}, {$gradientColor2})" }};"></div>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Start color</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input wire:model.live.debounce.300ms="gradientColor1" type="color" class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 dark:border-zinc-700">
                        <input wire:model.live.debounce.500ms="gradientColor1" type="text" class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-zinc-700" maxlength="7">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">End color</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input wire:model.live.debounce.300ms="gradientColor2" type="color" class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 dark:border-zinc-700">
                        <input wire:model.live.debounce.500ms="gradientColor2" type="text" class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-zinc-700" maxlength="7">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Direction</label>
                    <select wire:change="setDesign('gradientType', $event.target.value)" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-zinc-700">
                        <option value="linear" @selected($gradientType === 'linear')>Linear</option>
                        <option value="radial" @selected($gradientType === 'radial')>Radial</option>
                    </select>
                </div>
            </div>
        @endif
    </div>
</div>
