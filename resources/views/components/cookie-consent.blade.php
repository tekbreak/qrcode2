<div x-data="{ show: !localStorage.getItem('cookie_consent') }" x-show="show" x-transition
     class="fixed bottom-0 inset-x-0 z-50 p-4" x-cloak>
    <div class="mx-auto max-w-4xl rounded-xl bg-gray-900 px-6 py-4 shadow-2xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-300">
                We use cookies to enhance your experience and analyze site traffic. By continuing, you agree to our
                <a href="#" class="text-primary-400 underline hover:text-primary-300">Cookie Policy</a>.
            </p>
            <div class="flex shrink-0 gap-3">
                <button @click="localStorage.setItem('cookie_consent', 'declined'); show = false"
                        class="rounded-lg border border-gray-600 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-800 transition">
                    Decline
                </button>
                <button @click="localStorage.setItem('cookie_consent', 'accepted'); show = false"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>
