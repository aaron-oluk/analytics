<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · Analytics' : 'Analytics' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans antialiased text-zinc-900">
        <div
            x-data="shell"
            @keydown.escape.window="sidebarOpen = false"
            class="min-h-screen transition-[padding] duration-200"
            :class="sidebarExpanded ? 'lg:ps-64' : 'lg:ps-16'"
        >
            @include('layouts.sidebar')
            @include('layouts.navbar')

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
