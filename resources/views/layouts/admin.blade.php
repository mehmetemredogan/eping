<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin — Ping' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-950 antialiased" x-data="siteNav">
    <div class="flex min-h-screen">
        <aside class="hidden w-56 shrink-0 border-r border-neutral-950 bg-white md:block">
            <div class="flex h-14 items-center gap-2 border-b border-neutral-950 px-5">
                <span class="flex h-6 w-6 items-center justify-center border border-neutral-950 bg-neutral-950 text-[10px] text-white">>_</span>
                <a href="{{ route('ping.index') }}" class="text-sm font-semibold tracking-tight">PING</a>
            </div>
            <nav class="space-y-0.5 p-3 text-xs uppercase tracking-widest">
                <a href="{{ route('admin.dashboard') }}"
                   class="block px-3 py-2.5 {{ request()->routeIs('admin.dashboard') ? 'bg-neutral-950 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    {{ __('admin.nav_dashboard') }}
                </a>
                <a href="{{ route('admin.targets.index') }}"
                   class="block px-3 py-2.5 {{ request()->routeIs('admin.targets.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    {{ __('admin.nav_targets') }}
                </a>
                <a href="{{ route('admin.providers.index') }}"
                   class="block px-3 py-2.5 {{ request()->routeIs('admin.providers.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    {{ __('admin.nav_providers') }}
                </a>
                <a href="{{ route('admin.results.index') }}"
                   class="block px-3 py-2.5 {{ request()->routeIs('admin.results.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    {{ __('admin.nav_results') }}
                </a>
                <a href="{{ route('ping.index') }}" class="mt-4 block border-t border-neutral-200 px-3 py-2.5 pt-4 text-neutral-500 hover:bg-neutral-100">
                    ← {{ __('admin.nav_back_to_test') }}
                </a>
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="flex h-14 items-center justify-between gap-3 border-b border-neutral-950 bg-white px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center border border-neutral-950 text-neutral-950 md:hidden"
                        @click="toggle()"
                        :aria-expanded="open"
                    >
                        <span class="sr-only">{{ __('admin.menu') }}</span>
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 4h12M2 8h12M2 12h12" />
                        </svg>
                    </button>
                    <h1 class="truncate text-sm font-semibold uppercase tracking-widest">{{ $header ?? 'Admin' }}</h1>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1.5">
                        @csrf
                        <button type="submit" name="locale" value="tr" class="border border-neutral-950 px-2 py-1 text-[11px] {{ app()->getLocale() === 'tr' ? 'bg-neutral-950 text-white' : 'bg-white text-neutral-600' }}">TR</button>
                        <button type="submit" name="locale" value="en" class="border border-neutral-950 px-2 py-1 text-[11px] {{ app()->getLocale() === 'en' ? 'bg-neutral-950 text-white' : 'bg-white text-neutral-600' }}">EN</button>
                    </form>
                    <span class="mono hidden truncate text-xs text-neutral-500 sm:inline">{{ '@'.auth()->user()->username }}</span>
                </div>
            </header>

            <div
                x-show="open"
                x-cloak
                class="border-b border-neutral-950 bg-white md:hidden"
            >
                <nav class="flex flex-col gap-1 p-3 text-xs uppercase tracking-widest">
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-2.5 {{ request()->routeIs('admin.dashboard') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}">{{ __('admin.nav_dashboard') }}</a>
                    <a href="{{ route('admin.targets.index') }}" class="px-3 py-2.5 {{ request()->routeIs('admin.targets.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}">{{ __('admin.nav_targets') }}</a>
                    <a href="{{ route('admin.providers.index') }}" class="px-3 py-2.5 {{ request()->routeIs('admin.providers.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}">{{ __('admin.nav_providers') }}</a>
                    <a href="{{ route('admin.results.index') }}" class="px-3 py-2.5 {{ request()->routeIs('admin.results.*') ? 'bg-neutral-950 text-white' : 'text-neutral-600' }}">{{ __('admin.nav_results') }}</a>
                    <a href="{{ route('ping.index') }}" class="px-3 py-2.5 text-neutral-500">← {{ __('admin.nav_back_to_test') }}</a>
                </nav>
            </div>

            @if(session('success'))
                <div class="mx-4 mt-4 border border-neutral-950 bg-white px-4 py-3 text-sm sm:mx-6">
                    <span class="mr-2 text-green-600">[OK]</span>{{ session('success') }}
                </div>
            @endif

            <main class="p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
