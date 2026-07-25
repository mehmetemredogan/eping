@props([
    'variant' => 'select', // select | buttons
    'full' => false,
])

@if($variant === 'select')
    <form method="POST" action="{{ route('locale.update') }}" id="locale-form" {{ $attributes->class('hidden items-center gap-2 sm:flex') }}>
        @csrf
        <select
            id="locale-select"
            name="locale"
            class="js-select2 js-locale-select"
            aria-label="{{ __('ping.language') }}"
            data-minimum-results-for-search="Infinity"
            data-width="90px"
        >
            <option value="tr" @selected(app()->getLocale() === 'tr')>TR</option>
            <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
        </select>
    </form>
@else
    <form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['flex items-center gap-1.5', 'w-full' => $full]) }}>
        @csrf
        <button type="submit" name="locale" value="tr" @class([
            'border border-neutral-950 px-2 py-1.5 text-[11px]',
            'flex-1' => $full,
            'bg-neutral-950 text-white' => app()->getLocale() === 'tr',
            'bg-white text-neutral-600' => app()->getLocale() !== 'tr',
        ])>TR</button>
        <button type="submit" name="locale" value="en" @class([
            'border border-neutral-950 px-2 py-1.5 text-[11px]',
            'flex-1' => $full,
            'bg-neutral-950 text-white' => app()->getLocale() === 'en',
            'bg-white text-neutral-600' => app()->getLocale() !== 'en',
        ])>EN</button>
    </form>
@endif
