@extends('layouts.storefront')

@section('robots', 'noindex, follow')

@section('title', __('Too many requests').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Please wait a moment and try again.'))

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col items-center px-4 py-20 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-bold uppercase tracking-widest text-brand-700 dark:text-brand-400">429</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Too many requests') }}</h1>
        <p class="mt-4 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('Please wait a moment and try again.') }}</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">{{ __('Go to homepage') }}</a>
            <a href="{{ route('vehicles.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Browse cars') }}</a>
        </div>
    </div>
@endsection
