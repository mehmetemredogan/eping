<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Ping') }}</title>
    <x-favicon />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-neutral-950 antialiased" x-data="siteNav" @keydown.escape.window="close()">
    <header class="sticky top-0 z-50 border-b border-neutral-950 bg-white">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-6">
                <x-brand-mark />
                <x-ping-nav variant="desktop" />
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                <x-locale-switcher variant="select" />

                @auth
                    <span class="mono hidden max-w-[8rem] truncate text-xs text-neutral-500 lg:inline">{{ '@'.auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="text-xs uppercase tracking-widest text-neutral-500 transition-colors hover:text-neutral-950">{{ __('ping.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden text-xs uppercase tracking-widest text-neutral-500 transition-colors hover:text-neutral-950 sm:inline">{{ __('ping.login') }}</a>
                    <x-ui-button :href="route('register')" variant="primary" class="hidden px-3 py-1.5 text-xs uppercase tracking-widest sm:inline-flex">
                        {{ __('ping.register') }}
                    </x-ui-button>
                @endauth

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center border border-neutral-950 text-neutral-950 sm:hidden"
                    @click="toggle()"
                    :aria-expanded="open"
                    aria-controls="mobile-nav"
                >
                    <span class="sr-only">{{ __('ping.menu') }}</span>
                    <svg x-show="!open" class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M2 4h12M2 8h12M2 12h12" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 3l10 10M13 3L3 13" />
                    </svg>
                </button>
            </div>
        </div>

        <div
            id="mobile-nav"
            x-show="open"
            x-cloak
            x-transition.opacity
            class="border-t border-neutral-200 bg-white sm:hidden"
            @click.outside="close()"
        >
            <x-ping-nav variant="mobile" />
        </div>
    </header>

    <x-flash-alerts />

    <main>
        {{ $slot }}
    </main>

    <x-site-footer />
    <x-cookie-banner />
</body>
</html>
