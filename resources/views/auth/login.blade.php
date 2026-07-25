<x-guest-layout>
    <h1 class="mb-1 text-lg font-semibold tracking-tight">{{ __('ping.login_title') }}</h1>
    <p class="mb-6 text-xs leading-relaxed text-neutral-500">{{ __('ping.login_subtitle') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label for="username" :value="__('ping.username')" />
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            <x-input-error for="username" />
        </div>

        <div>
            <x-form-label for="password" :value="__('ping.password')" />
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            <x-input-error for="password" />
        </div>

        <label class="flex items-center gap-2 text-xs text-neutral-600">
            <input type="checkbox" name="remember" class="rounded-none border-neutral-300 text-neutral-950 focus:ring-neutral-950">
            {{ __('ping.remember') }}
        </label>

        <x-ui-button type="submit" variant="primary" block class="py-2.5">
            {{ __('ping.login') }} →
        </x-ui-button>
    </form>

    <p class="mt-6 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
        {{ __('ping.no_account') }}
        <a href="{{ route('register') }}" class="text-neutral-950 underline underline-offset-2">{{ __('ping.register') }}</a>
    </p>
</x-guest-layout>
