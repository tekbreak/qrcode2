<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('auth.verify_email') }}</h2>

    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {{ __('auth.verify_email_subtitle', ['email' => auth()->user()->email]) }}
    </p>

    @if($linkSent)
        <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-950/50 p-4 text-sm text-green-700 dark:text-green-400">
            {{ __('auth.verify_email_sent') }}
        </div>
    @endif

    @error('email') <p class="mt-4 text-sm text-red-600">{{ $message }}</p> @enderror

    <button type="button" wire:click="resend"
            class="mt-6 flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
        {{ __('auth.verify_email_resend') }}
    </button>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-primary-600 hover:text-primary-500">
            {{ __('nav.logout') }}
        </button>
    </form>
</div>
