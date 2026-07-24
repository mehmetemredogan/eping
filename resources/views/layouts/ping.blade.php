<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Ping') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-neutral-950 antialiased" x-data="siteNav" @keydown.escape.window="close()">
    <header class="sticky top-0 z-50 border-b border-neutral-950 bg-white">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-6">
                <a href="{{ route('ping.index') }}" class="flex shrink-0 items-center gap-2 text-sm font-semibold tracking-tight text-neutral-950">
                    <span class="flex h-6 w-6 items-center justify-center border border-neutral-950 bg-neutral-950 text-[10px] text-white">></span>
                    <span>PING</span>
                </a>
                <nav class="hidden items-center gap-5 text-xs uppercase tracking-widest sm:flex">
                    @auth
                        <a href="{{ route('history.index') }}" class="{{ request()->routeIs('history.*') ? 'border-b border-neutral-950 pb-0.5 text-neutral-950' : 'text-neutral-500 hover:text-neutral-950' }}">
                            {{ __('ping.nav_history') }}
                        </a>
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="text-neutral-500 hover:text-neutral-950">
                                {{ __('ping.nav_admin') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                <form method="POST" action="{{ route('locale.update') }}" id="locale-form" class="hidden items-center gap-2 sm:flex">
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

                @auth
                    <span class="mono hidden max-w-[8rem] truncate text-xs text-neutral-500 lg:inline">{{ '@'.auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="text-xs uppercase tracking-widest text-neutral-500 hover:text-neutral-950">{{ __('ping.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden text-xs uppercase tracking-widest text-neutral-500 hover:text-neutral-950 sm:inline">{{ __('ping.login') }}</a>
                    <a href="{{ route('register') }}" class="hidden border border-neutral-950 bg-neutral-950 px-3 py-1.5 text-xs font-medium uppercase tracking-widest text-white transition-colors hover:bg-white hover:text-neutral-950 sm:inline">{{ __('ping.register') }}</a>
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
            <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm">
                <a href="{{ route('ping.index') }}" class="px-2 py-2 uppercase tracking-widest {{ request()->routeIs('ping.index') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}" @click="close()">
                    {{ __('ping.nav_test') }}
                </a>
                @auth
                    <a href="{{ route('history.index') }}" class="px-2 py-2 uppercase tracking-widest {{ request()->routeIs('history.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}" @click="close()">
                        {{ __('ping.nav_history') }}
                    </a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="px-2 py-2 uppercase tracking-widest text-neutral-600" @click="close()">
                            {{ __('ping.nav_admin') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-neutral-100 pt-2">
                        @csrf
                        <button type="submit" class="w-full px-2 py-2 text-left uppercase tracking-widest text-neutral-600">{{ __('ping.logout') }} · {{ auth()->user()->username }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-2 py-2 uppercase tracking-widest text-neutral-600" @click="close()">{{ __('ping.login') }}</a>
                    <a href="{{ route('register') }}" class="px-2 py-2 uppercase tracking-widest text-neutral-600" @click="close()">{{ __('ping.register') }}</a>
                @endauth
                <form method="POST" action="{{ route('locale.update') }}" class="mt-1 flex gap-2 border-t border-neutral-100 pt-3">
                    @csrf
                    <button type="submit" name="locale" value="tr" class="flex-1 border border-neutral-950 px-2 py-1.5 text-xs {{ app()->getLocale() === 'tr' ? 'bg-neutral-950 text-white' : 'bg-white' }}">TR</button>
                    <button type="submit" name="locale" value="en" class="flex-1 border border-neutral-950 px-2 py-1.5 text-xs {{ app()->getLocale() === 'en' ? 'bg-neutral-950 text-white' : 'bg-white' }}">EN</button>
                </form>
            </nav>
        </div>
    </header>

    @if(session('success'))
        <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            <div class="border border-neutral-950 bg-white px-4 py-3 text-sm">
                <span class="mr-2 text-green-600">[OK]</span>{{ session('success') }}
            </div>
        </div>
    @endif

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-neutral-950 sm:mt-16">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <span class="mono">PING - mehmetemredogan.tr</span>
            <span class="mono">// {{ __('ping.footer_note') }}</span>
        </div>
    </footer>
</body>
</html>
