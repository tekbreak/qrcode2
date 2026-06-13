<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('qr.categories') }}</h1>
            <a href="{{ route('qr-codes.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                &larr; {{ __('qr.my_qr_codes') }}
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/50 px-4 py-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    {{-- Create category --}}
    <div class="mb-6 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('qr.create_category') }}</h2>
        <form wire:submit="create" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="flex-1">
                <input wire:model="name" type="text" placeholder="{{ __('qr.category_name_placeholder') }}"
                       class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <select wire:model="color" class="rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    @foreach($colorOptions as $key => $classes)
                        <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                {{ __('common.create') }}
            </button>
        </form>
    </div>

    {{-- Categories list --}}
    @if($categories->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-zinc-700 p-12 text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('qr.no_categories') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('qr.no_categories_desc') }}</p>
        </div>
    @else
        <div class="rounded-xl bg-white dark:bg-zinc-900 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('qr.category') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('qr.qr_codes_count') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                    @foreach($categories as $category)
                        <tr wire:key="category-{{ $category->id }}">
                            @if($editingId === $category->id)
                                <td class="px-4 py-3" colspan="2">
                                    <form wire:submit="update" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <input wire:model="editingName" type="text"
                                               class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                        <select wire:model="editingColor" class="rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                            @foreach($colorOptions as $key => $classes)
                                                <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-2">
                                            <button type="submit" class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">{{ __('common.save') }}</button>
                                            <button type="button" wire:click="cancelEdit" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('common.cancel') }}</button>
                                        </div>
                                    </form>
                                    @error('editingName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td></td>
                            @else
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $category->badgeClasses() }}">
                                        {{ $category->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $category->qr_codes_count }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button wire:click="startEdit({{ $category->id }})" class="text-xs font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400">{{ __('common.edit') }}</button>
                                        <button wire:click="delete({{ $category->id }})" wire:confirm="{{ __('qr.confirm_delete_category') }}"
                                                class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('common.delete') }}</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
