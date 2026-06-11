<div x-data="{ show: !localStorage.getItem('cookie_consent') }" x-show="show" x-transition
     class="fixed bottom-0 inset-x-0 z-50 p-4" x-cloak>
    <div class="mx-auto max-w-4xl rounded-xl bg-gray-900 px-6 py-4 shadow-2xl dark:bg-zinc-800 dark:ring-1 dark:ring-zinc-700">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-300 dark:text-gray-400">
                {{ __('landing.cookies.message') }}
                <a href="#" class="text-primary-400 underline hover:text-primary-300">{{ __('landing.cookies.policy') }}</a>.
            </p>
            <div class="flex shrink-0 gap-3">
                <button @click="localStorage.setItem('cookie_consent', 'declined'); show = false"
                        class="rounded-lg border border-gray-600 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 dark:border-zinc-600 dark:hover:bg-zinc-700">
                    {{ __('landing.cookies.decline') }}
                </button>
                <button @click="localStorage.setItem('cookie_consent', 'accepted'); show = false"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    {{ __('landing.cookies.accept') }}
                </button>
            </div>
        </div>
    </div>
</div>
