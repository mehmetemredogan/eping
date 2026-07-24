<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Ping - mehmetemredogan.tr') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-neutral-950 antialiased">
    <div class="bg-grid flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 text-base font-semibold tracking-tight">
            <span class="flex h-7 w-7 items-center justify-center border border-neutral-950 bg-neutral-950 text-[11px] text-white">>_</span>
            <span>PING</span>
        </a>
        <div class="w-full max-w-md border border-neutral-950 bg-white">
            <div class="flex items-center gap-1.5 border-b border-neutral-950 bg-neutral-50 px-4 py-2">
                <span class="h-2 w-2 rounded-full border border-neutral-950"></span>
                <span class="h-2 w-2 rounded-full border border-neutral-950"></span>
                <span class="h-2 w-2 rounded-full border border-neutral-950 bg-neutral-950"></span>
                <span class="mono ml-2 text-[10px] uppercase tracking-widest text-neutral-400">auth</span>
            </div>
            <div class="p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
