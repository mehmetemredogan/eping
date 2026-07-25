@props([
    'compact' => false,
])

@if($compact)
    <div {{ $attributes->class('mt-6') }}>
        <x-legal-nav variant="guest" />
    </div>
@else
    <footer {{ $attributes->class('mt-12 border-t border-neutral-950 sm:mt-16') }}>
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <x-brand-mark :href="route('home')" size="sm" :show-label="false" />
                    <span class="mono text-xs text-neutral-500">PING · mehmetemredogan.tr</span>
                </div>
                <span class="mono text-[11px] text-neutral-400">// {{ __('ping.footer_note') }}</span>
            </div>
            <x-legal-nav variant="footer" />
        </div>
    </footer>
@endif
