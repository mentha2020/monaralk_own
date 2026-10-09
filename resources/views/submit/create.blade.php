@extends('layouts.storefront')

@section('title', __('Sell your car').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Submit your car for sale on :site. Photos, specs and price — every listing is reviewed by our team before it goes live.', ['site' => Setting::get('site.name', 'Monaralk')]))

@section('content')
    <div class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/60">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-brand-700 dark:text-brand-400">{{ __('Free listing') }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Sell your car') }}</h1>
            <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                {{ __('Five short steps. Our team reviews every submission by hand, so your listing reaches buyers looking for exactly your car.') }}
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <livewire:submit-vehicle-wizard />
    </div>
@endsection
