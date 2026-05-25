<div>
    <h1 class="text-2xl font-bold text-gray-900">{{ __('nav.dashboard') }}</h1>
    <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}!</p>

    {{-- Stats grid --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-primary-100 p-2">
                    <x-icon name="qr-code" class="h-5 w-5 text-primary-600" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total QR Codes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_qr_codes']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2">
                    <x-icon name="chart-bar" class="h-5 w-5 text-green-600" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Scans</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_scans']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2">
                    <x-icon name="chart-bar" class="h-5 w-5 text-blue-600" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">Today's Scans</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['today_scans']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 p-2">
                    <x-icon name="credit-card" class="h-5 w-5 text-purple-600" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('nav.credits') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($creditBalance?->balance ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- Top QR Codes --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-lg font-semibold text-gray-900">Top QR Codes</h2>
            @if($topQrCodes->isEmpty())
                <p class="mt-4 text-sm text-gray-500">{{ __('qr.no_qr_codes_desc') }}</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach($topQrCodes as $qr)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $qr->name }}</p>
                                <p class="text-xs text-gray-500">{{ $qr->type->label() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">{{ number_format($qr->total_scans) }}</p>
                                <p class="text-xs text-gray-500">scans</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('qr-codes.create') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-primary-300 hover:bg-primary-50">
                    <div class="rounded-lg bg-primary-100 p-2">
                        <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('qr.create') }}</p>
                        <p class="text-xs text-gray-500">Create a new QR code</p>
                    </div>
                </a>
                <a href="{{ route('qr-codes.index') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-primary-300 hover:bg-primary-50">
                    <div class="rounded-lg bg-green-100 p-2">
                        <x-icon name="qr-code" class="h-5 w-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('qr.my_qr_codes') }}</p>
                        <p class="text-xs text-gray-500">Manage existing codes</p>
                    </div>
                </a>
                <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-primary-300 hover:bg-primary-50">
                    <div class="rounded-lg bg-blue-100 p-2">
                        <x-icon name="chart-bar" class="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('nav.analytics') }}</p>
                        <p class="text-xs text-gray-500">View scan statistics</p>
                    </div>
                </a>
                <a href="{{ route('billing.index') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-primary-300 hover:bg-primary-50">
                    <div class="rounded-lg bg-purple-100 p-2">
                        <x-icon name="credit-card" class="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('nav.billing') }}</p>
                        <p class="text-xs text-gray-500">Manage subscription</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
