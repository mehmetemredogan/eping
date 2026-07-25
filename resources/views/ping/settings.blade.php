<x-ping-layout>
    <x-page-shell>
        <x-page-header
            :eyebrow="__('ping.nav_settings')"
            :title="__('ping.settings_title')"
            :subtitle="__('ping.settings_subtitle')"
        />

        <div class="grid gap-6 lg:grid-cols-2">
            <x-panel :title="__('ping.settings_profile')">
                <form method="POST" action="{{ route('settings.profile') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-form-label for="username" :value="__('ping.username')" />
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
                        <x-input-error for="username" />
                    </div>

                    <x-ui-button type="submit" variant="primary">{{ __('ping.settings_save_profile') }}</x-ui-button>
                </form>
            </x-panel>

            <x-panel :title="__('ping.settings_password')">
                <form method="POST" action="{{ route('settings.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-form-label for="current_password" :value="__('ping.password_current')" />
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                        <x-input-error for="current_password" />
                    </div>

                    <div>
                        <x-form-label for="password" :value="__('ping.password_new')" />
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                        <x-input-error for="password" />
                    </div>

                    <div>
                        <x-form-label for="password_confirmation" :value="__('ping.password_confirm')" />
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="mono w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none focus:ring-0"
                        >
                    </div>

                    <x-ui-button type="submit" variant="primary">{{ __('ping.settings_save_password') }}</x-ui-button>
                </form>
            </x-panel>
        </div>

        <x-panel :title="__('ping.settings_history')" class="mt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
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
                    <x-ui-button type="submit" variant="danger" class="w-full sm:w-auto" :disabled="$historyCount === 0">
                        {{ __('ping.history_clear') }}
                    </x-ui-button>
                </form>
            </div>
        </x-panel>
    </x-page-shell>
</x-ping-layout>
