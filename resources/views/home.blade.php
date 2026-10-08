@extends('layouts.storefront')

@section('title', 'Buy and sell cars in Sri Lanka | '.Setting::get('site.name', 'Monaralk'))

@section('description', Setting::get('site.tagline', "Sri Lanka's trusted car marketplace").'. Browse verified new and used cars with full specs, photos and prices in LKR.')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.25),transparent_55%)]"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-100">
                    {{ $stats['listings'] }} {{ __('live listings') }}
                </p>

                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
                    {{ Setting::get('site.tagline', "Sri Lanka's trusted car marketplace") }}
                </h1>

                <p class="mt-4 max-w-xl text-base leading-7 text-brand-100 sm:text-lg">
                    {{ __('Search hundreds of verified new and used cars with full specifications, honest pricing in LKR and photos you can trust.') }}
                </p>
            </div>

            <form action="{{ route('vehicles.index') }}" method="GET"
                  class="mt-8 grid gap-3 rounded-2xl bg-white/95 p-4 shadow-xl ring-1 ring-white/30 sm:grid-cols-12 sm:p-5">
                <div class="sm:col-span-5">
                    <label for="hero-keyword" class="sr-only">{{ __('Keyword') }}</label>
                    <input
                        id="hero-keyword"
                        type="search"
                        name="keyword"
                        placeholder="{{ __('Make, model, keyword…') }}"
                        class="block w-full rounded-xl border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600"
                    >
                </div>

                <div class="sm:col-span-4">
                    <label for="hero-make" class="sr-only">{{ __('Make') }}</label>
                    <select id="hero-make" name="make_id" class="block w-full rounded-xl border-slate-200 text-slate-900 focus:border-brand-600 focus:ring-brand-600">
                        <option value="">{{ __('Any make') }}</option>
                        @foreach ($makes as $make)
                            <option value="{{ $make->id }}">{{ $make->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="hero-price" class="sr-only">{{ __('Max price') }}</label>
                    <select id="hero-price" name="max_price" class="block w-full rounded-xl border-slate-200 text-slate-900 focus:border-brand-600 focus:ring-brand-600">
                        <option value="">{{ __('Any price') }}</option>
                        <option value="2000000">Under 2M</option>
                        <option value="5000000">Under 5M</option>
                        <option value="10000000">Under 10M</option>
                        <option value="20000000">Under 20M</option>
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <button type="submit"
                            class="inline-flex h-[42px] w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 sm:h-full">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" /></svg>
                        <span class="sm:hidden">{{ __('Search') }}</span>
                    </button>
                </div>
            </form>

            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-brand-200">{{ __('Live listings') }}</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-white">{{ number_format($stats['listings']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-brand-200">{{ __('Brands') }}</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-white">{{ number_format($stats['makes']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-brand-200">{{ __('Featured deals') }}</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-white">{{ number_format($stats['featured']) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Featured cars') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Hand-picked deals worth a closer look.') }}</p>
                </div>
                <a href="{{ route('vehicles.index', ['featured' => 1]) }}"
                   class="hidden text-sm font-semibold text-brand-700 transition hover:text-brand-800 sm:inline dark:text-brand-400">
                    {{ __('View all') }} &rarr;
                </a>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $vehicle)
                    <x-listing.card :vehicle="$vehicle" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="border-y border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Latest arrivals') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Fresh on the market this week.') }}</p>
                </div>
                <a href="{{ route('vehicles.index') }}"
                   class="hidden text-sm font-semibold text-brand-700 transition hover:text-brand-800 sm:inline dark:text-brand-400">
                    {{ __('Browse all') }} &rarr;
                </a>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latest as $vehicle)
                    <x-listing.card :vehicle="$vehicle" />
                @endforeach
            </div>

            <div class="mt-8 text-center sm:hidden">
                <a href="{{ route('vehicles.index') }}"
                   class="inline-flex rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-800">
                    {{ __('Browse all cars') }}
                </a>
            </div>
        </div>
    </section>

    @if ($makes->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Browse by brand') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Jump straight to the make you already know.') }}</p>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($makes as $make)
                    <a href="{{ route('vehicles.index', ['make_id' => $make->id]) }}"
                       class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white p-5 text-center transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700">
                        @if ($make->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($make->logo_path) }}" alt="{{ $make->name }}" class="h-10 w-10 rounded-lg object-contain" loading="lazy">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-base font-extrabold text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white dark:bg-brand-950 dark:text-brand-400">
                                {{ mb_substr($make->name, 0, 1) }}
                            </span>
                        @endif
                        <span class="text-sm font-semibold text-slate-700 transition group-hover:text-brand-700 dark:text-slate-200 dark:group-hover:text-brand-400">{{ $make->name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $make->listings_count }} {{ __('cars') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-brand-950">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-extrabold tracking-tight text-white">{{ __('Why buyers trust Monaralk') }}</h2>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => __('Verified listings'), 'body' => __('Every car is reviewed by our team before it goes live.'), 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['title' => __('Full specifications'), 'body' => __('Mileage, provenance, service history and features in one place.'), 'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
                    ['title' => __('Honest pricing'), 'body' => __('Prices in LKR with no hidden fees or surprise mark-ups.'), 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33'],
                    ['title' => __('Talk to a human'), 'body' => __('Call or WhatsApp the seller directly from any listing.'), 'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z'],
                ] as $item)
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-700 text-white">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-white">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-brand-100/80">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
