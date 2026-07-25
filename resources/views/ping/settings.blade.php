<x-ping-layout>
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <div class="bg-grid mb-8 border border-neutral-950 px-4 py-6 sm:px-6">
            <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('ping.nav_settings') }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('ping.settings_title') }}</h1>
            <p class="mt-2 text-xs text-neutral-500 sm:text-sm">{{ __('ping.settings_subtitle') }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Profile --}}
            <section class="border border-neutral-950">
                <div class="border-b border-neutral-950 bg-neutral-50 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-widest">{{ __('ping.settings_profile') }}</h2>
                </div>
                <form method="POST" action="{{ route('settings.profile') }}" class="space-y-4 p-4 sm:p-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="username" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.username') }}</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username', $user->username) }}"
                            required
                            autocomplete="username"
                            pattern="[A-Za-z0-9_]{3,32}"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                        <p class="mt-1 text-[11px] text-neutral-400">{{ __('ping.username_hint') }}</p>
                        @error('username')
                            <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
                        {{ __('ping.settings_save_profile') }}
                    </button>
                </form>
            </section>

            {{-- Password --}}
            <section class="border border-neutral-950">
                <div class="border-b border-neutral-950 bg-neutral-50 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-widest">{{ __('ping.settings_password') }}</h2>
                </div>
                <form method="POST" action="{{ route('settings.password') }}" class="space-y-4 p-4 sm:p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password_current') }}</label>
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password_new') }}</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">[!] {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.password_confirm') }}</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                    </div>

                    <button type="submit" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
                        {{ __('ping.settings_save_password') }}
                    </button>
                </form>
            </section>
        </div>

        {{-- Clear history --}}
        <section class="mt-6 border border-neutral-950">
            <div class="border-b border-neutral-950 bg-neutral-50 px-4 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-widest">{{ __('ping.settings_history') }}</h2>
            </div>
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="min-w-0">
                    <p class="text-sm text-neutral-950">{{ __('ping.history_clear_hint') }}</p>
                    <p class="mono mt-1 text-xs text-neutral-500">{{ __('ping.history_clear_count', ['count' => $historyCount]) }}</p>
                </div>
                <form
                    method="POST"
                    action="{{ route('settings.history') }}"
                    onsubmit="return confirm(@js(__('ping.history_clear_confirm')));"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="w-full border border-red-700 bg-white px-4 py-2 text-sm font-medium text-red-700 transition-colors hover:bg-red-700 hover:text-white sm:w-auto"
                        @disabled($historyCount === 0)
                    >
                        {{ __('ping.history_clear') }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-ping-layout>
