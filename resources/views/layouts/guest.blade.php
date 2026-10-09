<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Monaralk') }}</title>
        <meta name="robots" content="noindex, nofollow">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <script>
            (function () {
                var stored = localStorage.getItem('monaralk-theme');
                var dark = stored
                    ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased dark:text-slate-100">
        <div class="relative min-h-screen">
            <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
                {{ __('Skip to content') }}
            </a>

            <div class="absolute inset-0 bg-gradient-to-b from-brand-100 via-brand-50 to-brand-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-950"></div>

            <button
                type="button"
                id="theme-toggle"
                class="absolute end-4 top-4 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-white/70 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-brand-400"
                aria-label="{{ __('Toggle dark mode') }}"
            >
                <svg class="block h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
                <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                </svg>
            </button>

            <div id="main" class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
                <a href="{{ url('/') }}" class="mb-6 flex flex-col items-center gap-3 focus:outline-none focus:ring-2 focus:ring-brand-600 rounded-xl">
                    <x-application-logo wordmark class="scale-125" />
                    <span class="text-sm font-medium text-brand-800 dark:text-brand-300">{{ __('Sri Lanka\'s car marketplace') }}</span>
                </a>

                <div class="w-full sm:max-w-md overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 dark:bg-slate-900 dark:shadow-none dark:ring-white/10">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <script>
            (function () {
                var root = document.documentElement;
                document.getElementById('theme-toggle').addEventListener('click', function () {
                    var isDark = root.classList.toggle('dark');
                    localStorage.setItem('monaralk-theme', isDark ? 'dark' : 'light');
                });
            })();
        </script>
    </body>
</html>
