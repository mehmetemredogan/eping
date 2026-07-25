<x-ping-layout :title="__('legal.privacy_title')">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
        <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('legal.nav_privacy') }}</p>
        <h1 class="text-2xl font-semibold tracking-tight">{{ __('legal.privacy_title') }}</h1>
        <p class="mono mt-2 text-[11px] text-neutral-400">{{ __('legal.privacy_updated') }}</p>
        <p class="mt-6 text-sm leading-relaxed text-neutral-700">{{ __('legal.privacy_intro') }}</p>

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-neutral-700">
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s1_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s1_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s2_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s2_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s3_title') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5">
                    @foreach(__('legal.privacy_s3_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s4_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s4_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s5_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s5_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s6_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s6_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.privacy_s7_title') }}</h2>
                <p class="mt-2">{{ __('legal.privacy_s7_body') }}</p>
            </section>
        </div>

        <p class="mt-10 flex flex-wrap gap-4 border-t border-neutral-200 pt-4 text-xs text-neutral-500">
            <a href="{{ route('legal.terms') }}" class="underline underline-offset-2 hover:text-neutral-950">{{ __('legal.nav_terms') }}</a>
            <a href="{{ route('legal.cookies') }}" class="underline underline-offset-2 hover:text-neutral-950">{{ __('legal.nav_cookies') }}</a>
        </p>
    </div>
</x-ping-layout>
