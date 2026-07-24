<x-guest-layout>
    <h1 class="mb-1 text-lg font-semibold tracking-tight">{{ __('ping.register_title') }}</h1>
    <p class="mb-6 text-xs leading-relaxed text-neutral-500">{{ __('ping.register_subtitle') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="username" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.username') }}</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                pattern="[A-Za-z0-9_]{3,32}"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                placeholder="user_name">
            <p class="mt-1 text-[11px] text-neutral-400">{{ __('ping.username_hint') }}</p>
            @error('username') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('password') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password_confirm') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
        </div>

        <div>
            <label for="captcha" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.captcha') }}</label>
            <div class="mb-2 flex items-center gap-3">
                <img src="{{ route('captcha.image') }}?t={{ time() }}" alt="captcha" id="captcha-image"
                    class="h-12 w-40 border border-neutral-950 bg-neutral-50 object-contain">
                <button type="button" onclick="document.getElementById('captcha-image').src='{{ route('captcha.image') }}?t=' + Date.now()"
                    class="mono text-xs text-neutral-500 underline-offset-2 hover:text-neutral-950 hover:underline">
                    ⟳ {{ __('ping.captcha_refresh') }}
                </button>
            </div>
            <input id="captcha" type="text" name="captcha" required autocomplete="off"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm uppercase focus:border-neutral-950 focus:outline-none focus:ring-0">
            @error('captcha') <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full border border-neutral-950 bg-neutral-950 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            {{ __('ping.register') }} →
        </button>
    </form>

    <p class="mt-6 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
        {{ __('ping.have_account') }}
        <a href="{{ route('login') }}" class="text-neutral-950 underline underline-offset-2">{{ __('ping.login') }}</a>
    </p>
</x-guest-layout>
