<x-guest-layout>
    <h1 class="mb-1 text-lg font-semibold tracking-tight">{{ __('ping.login_title') }}</h1>
    <p class="mb-6 text-xs leading-relaxed text-neutral-500">{{ __('ping.login_subtitle') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="username" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.username') }}</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('username') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('password') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-xs text-neutral-600">
            <input type="checkbox" name="remember" class="rounded-none border-neutral-300 text-neutral-950 focus:ring-neutral-950">
            {{ __('ping.remember') }}
        </label>

        <button type="submit" class="w-full border border-neutral-950 bg-neutral-950 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            {{ __('ping.login') }} →
        </button>
    </form>

    <p class="mt-6 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
        {{ __('ping.no_account') }}
        <a href="{{ route('register') }}" class="text-neutral-950 underline underline-offset-2">{{ __('ping.register') }}</a>
    </p>
</x-guest-layout>
