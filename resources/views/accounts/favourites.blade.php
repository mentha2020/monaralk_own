@extends('layouts.storefront')

@section('title', __('Favourites').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('The cars you saved on :site.', ['site' => Setting::get('site.name', 'Monaralk')]))

@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-700 dark:text-brand-400">{{ __('Your account') }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Favourites') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Cars you saved for later. Saved to your account, so they follow you across devices.') }}
                </p>
            </div>

            <div class="flex items-center gap-3 text-sm font-semibold">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-brand-800 dark:bg-brand-950 dark:text-brand-300">
                    {{ __('Favourites') }} <span class="tabular-nums">{{ $counts['favourite'] }}</span>
                </span>
                <a href="{{ route('saved.compare') }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-600 hover:text-brand-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-brand-500 dark:hover:text-brand-400">
                    {{ __('Compare') }} <span class="tabular-nums">{{ $counts['compare'] }}</span>
                </a>
            </div>
        </div>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($vehicles->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                <p class="text-base font-semibold text-slate-900 dark:text-white">{{ __('No favourites yet') }}</p>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Tap Save on any listing and it will be waiting for you here.') }}
                </p>
                <a href="{{ route('vehicles.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    {{ __('Browse cars') }}
                </a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vehicles as $vehicle)
                    <x-listing.card :vehicle="$vehicle" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
