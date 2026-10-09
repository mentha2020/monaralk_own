<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Monaralk'))</title>

        @hasSection('description')
            <meta name="description" content="@yield('description')">
        @endif
        @hasSection('canonical')
            <link rel="canonical" href="@yield('canonical')">
        @endif
        <meta property="og:site_name" content="{{ Setting::get('site.name', 'Monaralk') }}">
        <meta property="og:type" content="website">
        @hasSection('title')
            <meta property="og:title" content="@yield('title')">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <script>
            (function () {
                var stored = localStorage.getItem('monaralk-theme');
                var dark = stored
                    ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <script>
            window.Monaralk = {
                keys: { favourite: 'monaralk.favourites', compare: 'monaralk.compare' },
                cap: {{ \App\Modules\Accounts\Services\SavedVehicles::COMPARE_CAP }},
                get: function (type) {
                    try {
                        return JSON.parse(localStorage.getItem(this.keys[type])) || [];
                    } catch (error) {
                        return [];
                    }
                },
                set: function (type, ids) {
                    localStorage.setItem(this.keys[type], JSON.stringify(ids));
                },
                has: function (id, type) {
                    return this.get(type).indexOf(Number(id)) !== -1;
                },
                toggle: function (id, type) {
                    id = Number(id);
                    var ids = this.get(type);
                    var at = ids.indexOf(id);

                    if (at === -1) {
                        if (type === 'compare' && ids.length >= this.cap) {
                            return false;
                        }
                        ids.push(id);
                    } else {
                        ids.splice(at, 1);
                    }

                    this.set(type, ids);

                    return true;
                },
                counts: function () {
                    return {
                        favourite: this.get('favourite').length,
                        compare: this.get('compare').length,
                    };
                },
                announce: function (counts) {
                    window.dispatchEvent(new CustomEvent('saved-changed', { detail: counts || this.counts() }));
                },
                merge: function () {
                    if (!document.body || document.body.dataset.savedMerge !== '1') {
                        return;
                    }

                    var payload = { favourite: this.get('favourite'), compare: this.get('compare') };

                    if (!payload.favourite.length && !payload.compare.length) {
                        return;
                    }

                    var token = document.querySelector('meta[name="csrf-token"]');

                    fetch('{{ route('saved.merge') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': token ? token.content : '',
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('merge failed');
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            window.Monaralk.set('favourite', []);
                            window.Monaralk.set('compare', []);
                            window.Monaralk.announce(data && data.counts ? data.counts : undefined);
                        })
                        .catch(function () {});
                },
            };

            document.addEventListener('DOMContentLoaded', function () {
                window.Monaralk.merge();
            });

            document.addEventListener('alpine:init', function () {
                Alpine.data('savedBadges', function (initial) {
                    return {
                        counts: initial || window.Monaralk.counts(),
                        init: function () {
                            var self = this;

                            window.addEventListener('saved-changed', function (event) {
                                self.counts = event.detail;
                            });
                        },
                    };
                });

                Alpine.data('saveActions', function (vehicleId) {
                    return {
                        favourite: window.Monaralk.has(vehicleId, 'favourite'),
                        compare: window.Monaralk.has(vehicleId, 'compare'),
                        notice: '',
                        toggle: function (type) {
                            if (type === 'compare' && !this.compare && window.Monaralk.get('compare').length >= window.Monaralk.cap) {
                                this.notice = 'Compare is limited to ' + window.Monaralk.cap + ' cars.';

                                return;
                            }

                            window.Monaralk.toggle(vehicleId, type);
                            this.favourite = window.Monaralk.has(vehicleId, 'favourite');
                            this.compare = window.Monaralk.has(vehicleId, 'compare');
                            this.notice = '';
                            window.Monaralk.announce();
                        },
                    };
                });
            });
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-200" data-saved-merge="{{ auth()->check() ? '1' : '0' }}">
        <div class="min-h-screen flex flex-col">
            <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
                Skip to content
            </a>

            <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
                <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-8">
                        <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="{{ Setting::get('site.name', 'Monaralk') }} home">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-lg font-extrabold text-white">M</span>
                            <span class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-white">
                                {{ Setting::get('site.name', 'Monaralk') }}
                            </span>
                        </a>

                        <nav class="hidden items-center gap-1 sm:flex" aria-label="Primary">
                            <a href="{{ route('home') }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('home') ? 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white' }}">
                                {{ __('Home') }}
                            </a>
                            <a href="{{ route('vehicles.index') }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('vehicles.*') ? 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white' }}">
                                {{ __('Browse Cars') }}
                            </a>
                            <a href="{{ route('submit.create') }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('submit.*') ? 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white' }}">
                                {{ __('Sell Your Car') }}
                            </a>
                            <a href="{{ route('contact') }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('contact*') ? 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white' }}">
                                {{ __('Contact') }}
                            </a>
                        </nav>
                    </div>

                    <div class="flex items-center gap-1 sm:gap-2">
                        <button
                            type="button"
                            data-theme-toggle
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-brand-400"
                            aria-label="{{ __('Toggle dark mode') }}"
                        >
                            <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                            </svg>
                            <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                            </svg>
                        </button>

                        <div
                            x-data="savedBadges({{ \Illuminate\Support\Js::from(auth()->check() ? app(\App\Modules\Accounts\Services\SavedVehicles::class)->counts((int) auth()->id()) : null) }})"
                            class="flex items-center gap-1"
                        >
                            <a href="{{ route('saved.favourites') }}"
                               class="inline-flex items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white"
                               aria-label="{{ __('Favourites') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                </svg>
                                <span class="text-xs font-bold" x-text="counts.favourite">0</span>
                            </a>

                            <a href="{{ route('saved.compare') }}"
                               class="inline-flex items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white"
                               aria-label="{{ __('Compare') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 3h5v5M8 3H3v5M21 3l-7 7M3 3l7 7M16 21h5v-5M8 21H3v-5M21 21l-7-7M3 21l7-7" />
                                </svg>
                                <span class="text-xs font-bold" x-text="counts.compare">0</span>
                            </a>
                        </div>

                        @auth
                            <div class="hidden items-center gap-2 sm:flex">
                                @if (auth()->user()?->isStaff())
                                    <a href="{{ url('/admin') }}"
                                       class="rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950">
                                        {{ __('Admin') }}
                                    </a>
                                @endif

                                <a href="{{ route('profile.edit') }}"
                                   class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white">
                                    {{ auth()->user()->name }}
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white">
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="hidden items-center gap-2 sm:flex">
                                <a href="{{ route('login') }}"
                                   class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white">
                                    {{ __('Log in') }}
                                </a>
                                <a href="{{ route('register') }}"
                                   class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                                    {{ __('Sign up') }}
                                </a>
                            </div>
                        @endauth

                        <button
                            type="button"
                            @click="open = ! open"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-600 sm:hidden dark:text-slate-400 dark:hover:bg-white/10"
                            aria-label="{{ __('Toggle menu') }}"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="sm:hidden">
                    <div :class="{'hidden': ! open, 'block': open}" class="hidden border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                        <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Home') }}</a>
                        <a href="{{ route('vehicles.index') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Browse Cars') }}</a>
                        <a href="{{ route('submit.create') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Sell Your Car') }}</a>
                        <a href="{{ route('contact') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Contact') }}</a>
                        <a href="{{ route('saved.favourites') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Favourites') }}</a>
                        <a href="{{ route('saved.compare') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Compare') }}</a>
                        @auth
                            @if (auth()->user()?->isStaff())
                                <a href="{{ url('/admin') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-brand-700 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950">{{ __('Admin') }}</a>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Profile') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Log out') }}</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">{{ __('Log in') }}</a>
                            <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-brand-700 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-950">{{ __('Sign up') }}</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main id="main" class="flex-1">
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-lg font-extrabold text-white">M</span>
                            <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ Setting::get('site.name', 'Monaralk') }}</span>
                        </div>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ Setting::get('site.tagline', "Sri Lanka's trusted car marketplace") }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Browse') }}</h3>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li><a href="{{ route('vehicles.index') }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('All cars') }}</a></li>
                            <li><a href="{{ route('vehicles.index', ['condition' => 'new']) }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Brand new') }}</a></li>
                            <li><a href="{{ route('vehicles.index', ['condition' => 'used']) }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Used cars') }}</a></li>
                            <li><a href="{{ route('vehicles.index', ['sort' => 'price_asc']) }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Most affordable') }}</a></li>
                            <li><a href="{{ route('submit.create') }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Sell your car') }}</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Account') }}</h3>
                        <ul class="mt-4 space-y-2 text-sm">
                            @auth
                                <li><a href="{{ route('profile.edit') }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Profile') }}</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Log in') }}</a></li>
                                <li><a href="{{ route('register') }}" class="text-slate-600 transition hover:text-brand-700 dark:text-slate-400 dark:hover:text-brand-400">{{ __('Create an account') }}</a></li>
                            @endauth
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Contact') }}</h3>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                            <li><a href="{{ route('contact') }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ __('Contact us') }}</a></li>
                            @if ($phone = Setting::get('contact.phone'))
                                <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ $phone }}</a></li>
                            @endif
                            @if ($email = Setting::get('contact.email'))
                                <li><a href="mailto:{{ $email }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ $email }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-800">
                    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <p>&copy; {{ date('Y') }} {{ Setting::get('site.name', 'Monaralk') }}. {{ __('All rights reserved.') }}</p>
                        <p>{{ __('Prices are listed in Sri Lankan Rupees.') }}</p>
                    </div>
                </div>
            </footer>
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

        @stack('scripts')
    </body>
</html>
