<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Monaralk') }}</title>

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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-slate-950">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white shadow dark:bg-slate-900 dark:shadow-none dark:border-b dark:border-slate-800">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            (function () {
                var root = document.documentElement;
                var buttons = document.querySelectorAll('[data-theme-toggle]');
                Array.prototype.forEach.call(buttons, function (button) {
                    button.addEventListener('click', function () {
                        var isDark = root.classList.toggle('dark');
                        localStorage.setItem('monaralk-theme', isDark ? 'dark' : 'light');
                    });
                });
            })();
        </script>
    </body>
</html>
