<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('qr.my_qr_codes') }}</h1>
            <a href="{{ route('categories.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                {{ __('qr.show_categories') }}
            </a>
        </div>
        <a href="{{ route('qr-codes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('qr.create') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('common.search') }}..."
                   class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
        </div>
        <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            <option value="">All Types</option>
            @foreach(\App\Enums\QrCodeType::allTypes() as $qrType)
                <option value="{{ $qrType->value }}">{{ $qrType->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            <option value="">All Status</option>
            <option value="active">{{ __('common.active') }}</option>
            <option value="paused">Paused</option>
        </select>
        @if($categories->isNotEmpty())
            <select wire:model.live="filterCategory" class="rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <option value="">{{ __('qr.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- QR Code Grid --}}
    @if($qrCodes->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-zinc-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('qr.no_qr_codes') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ __('qr.no_qr_codes_desc') }}</p>
            <a href="{{ route('qr-codes.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                {{ __('qr.create') }}
            </a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($qrCodes as $qr)
                <div class="group flex h-full flex-col rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $qr->name }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                @if($qr->category)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $qr->category->badgeClasses() }}">
                                        {{ $qr->category->name }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400 dark:text-gray-500">
                                    {{ $qr->type->label() }}
                                </span>
                                @if($qr->is_dynamic)
                                    <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-400">Dynamic</span>
                                @endif
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $qr->status === 'active' ? 'bg-green-100 text-green-700 dark:text-green-400' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($qr->status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($displayPreview = $qr->getCardDisplayUrl())
                        @php($copyValue = $qr->getCardCopyValue())
                        <div class="mt-3 flex items-center gap-2" data-copy-wrapper>
                            <code class="flex-1 truncate rounded bg-gray-50 dark:bg-zinc-800/60 px-2 py-1 text-xs text-gray-600 dark:text-gray-400 dark:text-gray-500">{{ $displayPreview }}</code>
                            <span data-copy-source class="sr-only">{{ $copyValue }}</span>
                            <button type="button"
                                    onclick="copyQrText(this)"
                                    title="{{ __('qr.copy_link') }}"
                                    class="rounded p-1 text-gray-400 dark:text-gray-500 hover:text-primary-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            </button>
                        </div>
                    @endif

                    <div class="mt-auto pt-3">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                            <span>{{ number_format($qr->total_scans) }} {{ __('qr.scans') }}</span>
                            <span>{{ $qr->created_at->diffForHumans() }}</span>
                        </div>

                        <div class="mt-3 flex items-center justify-end gap-2">
                        @if($qr->is_dynamic)
                        <a href="{{ route('qr-codes.edit', $qr) }}"
                           title="{{ __('common.edit') }}"
                           class="rounded p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </a>
                        @else
                        <a href="#"
                           wire:click.prevent="view({{ $qr->id }})"
                           title="{{ __('qr.view_content') }}"
                           class="rounded p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </a>
                        @endif
                        <a href="#"
                           wire:click.prevent="downloadPng({{ $qr->id }})"
                           title="{{ __('common.download') }}"
                           class="rounded p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            <i class="fa-solid fa-download text-sm"></i>
                        </a>
                        @if($qr->is_dynamic)
                        <a href="{{ route('analytics.show', $qr) }}"
                           title="{{ __('nav.analytics') }}"
                           class="rounded p-1.5 text-primary-500 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition">
                            <i class="fa-solid fa-chart-line text-sm"></i>
                        </a>
                        @endif
                        <a href="#"
                           wire:click.prevent="delete({{ $qr->id }})"
                           wire:confirm="{{ __('qr.confirm_delete') }}"
                           title="{{ __('common.delete') }}"
                           class="rounded p-1.5 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition">
                            <i class="fa-solid fa-trash text-sm"></i>
                        </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $qrCodes->links() }}
        </div>
    @endif

    @if($viewingQr)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="closeView">
            <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900" wire:click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $viewingQr->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $viewingQr->type->label() }}</p>
                    </div>
                    <button type="button" wire:click="closeView" title="{{ __('common.cancel') }}"
                            class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-zinc-800 dark:hover:text-gray-200">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <dl class="mt-6 space-y-4">
                    @foreach($viewingQr->getContentFields() as $field)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap break-words text-sm text-gray-900 dark:text-gray-100">{{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 border-t border-gray-200 pt-4 dark:border-zinc-800">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('qr.encoded_data') }}</h3>
                    <pre class="mt-2 max-h-48 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-700 dark:bg-zinc-800/60 dark:text-gray-300">{{ $viewingQr->getEncodedContent() }}</pre>
                </div>
            </div>
        </div>
    @endif
</div>
