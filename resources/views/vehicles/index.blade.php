@extends('layouts.storefront')

@section('title', __('Browse cars').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Search new and used cars by make, model, price, year, mileage and location. Prices in Sri Lankan Rupees.'))

@section('content')
    <div class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500 dark:text-slate-400">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="font-medium hover:text-brand-700 dark:hover:text-brand-400">{{ __('Home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-semibold text-slate-800 dark:text-slate-200">{{ __('Browse cars') }}</li>
                </ol>
            </nav>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                {{ __('Browse cars') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                {{ __('Filter by make, price, year, mileage and more. Every listing shows the full specification sheet.') }}
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <livewire:vehicle-search />
    </div>
@endsection
