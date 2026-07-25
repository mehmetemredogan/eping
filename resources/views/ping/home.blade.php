<x-ping-layout :title="__('ping.home_title')">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <section class="bg-grid border border-neutral-950 px-4 py-10 sm:px-8 sm:py-14">
            <p class="mono mb-3 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// ePing</p>
            <h1 class="max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('ping.home_brand') }}</h1>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-neutral-600 sm:text-base">{{ __('ping.home_lead') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <a
                    href="https://github.com/mehmetemredogan/eping"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center border border-neutral-950 bg-neutral-950 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950"
                >
                    {{ __('ping.home_download') }}
                </a>
                <a
                    href="{{ route('stats.index') }}"
                    class="inline-flex items-center justify-center border border-neutral-950 bg-white px-5 py-3 text-sm font-medium text-neutral-950 transition-colors hover:bg-neutral-950 hover:text-white"
                >
                    {{ __('ping.home_view_stats') }}
                </a>
            </div>
            <p class="mono mt-4 text-[11px] text-neutral-400">{{ __('ping.home_download_hint') }}</p>
        </section>

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

        <section class="mt-10 border border-neutral-950 bg-white p-6 sm:p-8">
            <h2 class="text-lg font-semibold tracking-tight">{{ __('ping.home_steps_title') }}</h2>
            <ol class="mt-5 space-y-4 text-sm text-neutral-700">
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
        </section>
    </div>
</x-ping-layout>
