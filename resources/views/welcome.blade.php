<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} &mdash; self-hosted, cookie-free website analytics</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col bg-gray-50">

            <header class="w-full">
                <div class="max-w-5xl mx-auto px-6 py-6 flex items-center justify-between">
                    <a href="/" class="flex items-center gap-2">
                        <x-application-logo class="w-8 h-8 fill-current text-indigo-600" />
                        <span class="font-semibold text-lg">{{ config('app.name') }}</span>
                    </a>

                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                                Go to dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                Log in
                            </a>
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition">
                                Sign up free
                            </a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                <section class="max-w-5xl mx-auto px-6 pt-12 pb-16 text-center">
                    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900">
                        Website analytics without the cookie banner
                    </h1>
                    <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                        Add a site, paste one script tag, and watch pageviews, sessions, and referrers roll in
                        no cookies, no third-party trackers, no consent popup required.
                    </p>

                    <div class="mt-8 flex items-center justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-indigo-500 transition">
                                Go to your dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-indigo-500 transition">
                                Create a free account
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-50 transition">
                                Log in
                            </a>
                        @endauth
                    </div>
                </section>

                <section class="max-w-5xl mx-auto px-6 pb-20">
                    <div class="grid sm:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="font-semibold text-gray-900">No cookies, no consent banner</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                Visitors are recognized only within a single day, using a salted hash that rotates
                                at midnight. Nothing links one day to the next.
                            </p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="font-semibold text-gray-900">One script tag</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                Paste a small snippet before <code class="text-xs">&lt;/body&gt;</code> on any site
                                you own and pageviews start showing up on your dashboard immediately.
                            </p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="font-semibold text-gray-900">Built to stay fast</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                History is pre-aggregated nightly, so your dashboard stays quick to load no matter
                                how much traffic you track.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="w-full border-t border-gray-200">
                <div class="max-w-5xl mx-auto px-6 py-6 text-sm text-gray-500">
                    {{ config('app.name') }}
                </div>
            </footer>
        </div>
    </body>
</html>
