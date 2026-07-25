<x-ping-layout :title="__('legal.terms_title')">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
        <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ __('legal.nav_terms') }}</p>
        <h1 class="text-2xl font-semibold tracking-tight">{{ __('legal.terms_title') }}</h1>
        <p class="mono mt-2 text-[11px] text-neutral-400">{{ __('legal.terms_updated') }}</p>
        <p class="mt-6 text-sm leading-relaxed text-neutral-700">{{ __('legal.terms_intro') }}</p>

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-neutral-700">
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s1_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s1_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s2_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s2_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s3_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s3_intro') }}</p>
                <ul class="mt-3 list-disc space-y-2 pl-5">
                    @foreach(__('legal.terms_s3_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s4_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s4_body') }}</p>
            </section>
            <section class="border border-neutral-950 bg-neutral-50 p-4 sm:p-5">
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s5_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s5_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s6_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s6_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s7_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s7_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s8_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s8_body') }}</p>
            </section>
            <section>
                <h2 class="text-base font-semibold text-neutral-950">{{ __('legal.terms_s9_title') }}</h2>
                <p class="mt-2">{{ __('legal.terms_s9_body') }}</p>
            </section>
        </div>

        <p class="mt-10 flex flex-wrap gap-4 border-t border-neutral-200 pt-4 text-xs text-neutral-500">
            <a href="{{ route('legal.privacy') }}" class="underline underline-offset-2 hover:text-neutral-950">{{ __('legal.nav_privacy') }}</a>
            <a href="{{ route('legal.cookies') }}" class="underline underline-offset-2 hover:text-neutral-950">{{ __('legal.nav_cookies') }}</a>
        </p>
    </div>
</x-ping-layout>
