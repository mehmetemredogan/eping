<div
    x-data="cookieNotice"
    x-cloak
    x-show="visible"
    x-transition.opacity
    class="fixed inset-x-0 bottom-0 z-[60] border-t border-neutral-950 bg-white p-4 shadow-[0_-8px_24px_rgba(0,0,0,0.06)] sm:p-5"
    role="dialog"
    aria-live="polite"
    aria-label="{{ __('legal.nav_cookies') }}"
>
    <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <p class="text-xs leading-relaxed text-neutral-600 sm:text-sm">
            {{ __('legal.banner_text') }}
            <a href="{{ route('legal.cookies') }}" class="ml-1 font-medium text-neutral-950 underline underline-offset-2">{{ __('legal.banner_learn') }}</a>
        </p>
        <button
            type="button"
            @click="accept()"
            class="shrink-0 border border-neutral-950 bg-neutral-950 px-4 py-2 text-xs font-medium uppercase tracking-widest text-white transition-colors hover:bg-white hover:text-neutral-950"
        >
            {{ __('legal.banner_accept') }}
        </button>
    </div>
</div>
