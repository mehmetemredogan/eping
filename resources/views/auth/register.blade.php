<x-guest-layout>
    <h1 class="mb-1 text-lg font-semibold tracking-tight">{{ __('ping.register_title') }}</h1>
    <p class="mb-6 text-xs leading-relaxed text-neutral-500">{{ __('ping.register_subtitle') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label for="username" :value="__('ping.username')" />
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                pattern="[A-Za-z0-9_]{3,32}"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                placeholder="user_name">
            <p class="mt-1 text-[11px] text-neutral-400">{{ __('ping.username_hint') }}</p>
            <x-input-error for="username" />
        </div>

        <div>
            <x-form-label for="password" :value="__('ping.password')" />
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
            <x-input-error for="password" />
        </div>

        <div>
            <x-form-label for="password_confirmation" :value="__('ping.password_confirm')" />
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0">
        </div>

        <div>
            <x-form-label for="captcha" :value="__('ping.captcha')" />
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
            <x-input-error for="captcha" />
        </div>

        <div class="border border-neutral-200 bg-neutral-50 p-3">
            <label class="flex items-start gap-2.5 text-xs leading-relaxed text-neutral-600">
                <input
                    id="terms"
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                    @checked(old('terms'))
                    class="mt-0.5 h-4 w-4 shrink-0 border-neutral-400 text-neutral-950 focus:ring-neutral-950"
                >
                <span>
                    {!! __('legal.register_accept_label', [
                        'terms' => '<a href="'.e(route('legal.terms')).'" target="_blank" rel="noopener" class="font-medium text-neutral-950 underline underline-offset-2">'.e(__('legal.register_accept_terms')).'</a>',
                        'privacy' => '<a href="'.e(route('legal.privacy')).'" target="_blank" rel="noopener" class="font-medium text-neutral-950 underline underline-offset-2">'.e(__('legal.register_accept_privacy')).'</a>',
                    ]) !!}
                </span>
            </label>
            <x-input-error for="terms" class="mt-2" />
        </div>

        <x-ui-button type="submit" variant="primary" block class="py-2.5">
            {{ __('ping.register') }} →
        </x-ui-button>
    </form>

    <p class="mt-6 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
        {{ __('ping.have_account') }}
        <a href="{{ route('login') }}" class="text-neutral-950 underline underline-offset-2">{{ __('ping.login') }}</a>
    </p>
</x-guest-layout>
