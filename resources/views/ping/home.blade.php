<x-ping-layout :title="__('ping.home_title')">
    <x-page-shell>
        <x-page-header
            eyebrow="ePing"
            :title="__('ping.home_brand')"
            :subtitle="__('ping.home_lead')"
            variant="hero"
        >
            <x-slot:actions>
                <x-ui-button href="https://github.com/mehmetemredogan/eping" variant="primary" class="px-5 py-3" target="_blank" rel="noopener noreferrer">
                    {{ __('ping.home_download') }}
                </x-ui-button>
                <x-ui-button :href="route('stats.index')" variant="secondary" class="px-5 py-3">
                    {{ __('ping.home_view_stats') }}
                </x-ui-button>
            </x-slot:actions>
            <x-slot:meta>{{ __('ping.home_download_hint') }}</x-slot:meta>
        </x-page-header>

        <section class="mt-10 grid gap-6 border border-neutral-200 bg-neutral-50 p-6 sm:grid-cols-3 sm:p-8">
            <div>
                <h2 class="text-xs font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.home_why_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-neutral-700">{{ __('ping.home_why_body') }}</p>
            </div>
            <div>
                <h2 class="text-xs font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.home_how_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-neutral-700">{{ __('ping.home_how_body') }}</p>
            </div>
            <div>
                <h2 class="text-xs font-medium uppercase tracking-widest text-neutral-400">{{ __('ping.home_data_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-neutral-700">{{ __('ping.home_data_body') }}</p>
            </div>
        </section>

        <x-panel :title="__('ping.home_steps_title')" variant="plain" class="mt-10">
            <ol class="space-y-4 text-sm text-neutral-700">
                <li class="flex gap-3">
                    <span class="mono shrink-0 text-neutral-400">01</span>
                    <span>{{ __('ping.home_step_1') }}</span>
                </li>
                <li class="flex gap-3">
                    <span class="mono shrink-0 text-neutral-400">02</span>
                    <span>{{ __('ping.home_step_2') }}</span>
                </li>
                <li class="flex gap-3">
                    <span class="mono shrink-0 text-neutral-400">03</span>
                    <span>{{ __('ping.home_step_3') }}</span>
                </li>
            </ol>
            <div class="mt-6">
                <a
                    href="https://github.com/mehmetemredogan/eping/releases"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-medium text-neutral-950 underline decoration-neutral-300 underline-offset-4 hover:decoration-neutral-950"
                >
                    {{ __('ping.home_releases') }}
                </a>
            </div>
        </x-panel>
    </x-page-shell>
</x-ping-layout>
