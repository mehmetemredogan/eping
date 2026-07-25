<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin — Ping' }}</title>
    <x-favicon />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-950 antialiased" x-data="siteNav" @keydown.escape.window="close()">
    <div class="flex min-h-screen">
        <aside class="hidden w-56 shrink-0 border-r border-neutral-950 bg-white md:block">
            <div class="flex h-14 items-center gap-2 border-b border-neutral-950 px-5">
                <x-brand-mark :href="route('admin.dashboard')" />
            </div>
            <x-admin-nav variant="sidebar" />
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
                        <svg x-show="!open" class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 4h12M2 8h12M2 12h12" />
                        </svg>
                        <svg x-show="open" x-cloak class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M3 3l10 10M13 3L3 13" />
                        </svg>
                    </button>
                    <h1 class="truncate text-sm font-semibold uppercase tracking-widest">{{ $header ?? 'Admin' }}</h1>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <x-locale-switcher variant="buttons" />
                    <span class="mono hidden truncate text-xs text-neutral-500 sm:inline">{{ '@'.auth()->user()->username }}</span>
                </div>
            </header>

            <div x-show="open" x-cloak x-transition.opacity class="border-b border-neutral-950 bg-white md:hidden">
                <x-admin-nav variant="mobile" />
            </div>

            <x-flash-alerts :padded="false" />

            <main class="p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
