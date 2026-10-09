@extends('layouts.storefront')

@section('title', __('Profile').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Update your Monaralk account information and password.'))

@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-700 dark:text-brand-400">{{ __('Your account') }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Profile') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __("Update your account's profile information and email address.") }}
                </p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-600 hover:text-brand-700 dark:border-slate-600 dark:text-slate-200 dark:hover:border-brand-500 dark:hover:text-brand-400">
                {{ __('Dashboard') }}
            </a>
        </div>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8 grid gap-6 lg:max-w-3xl">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                @include('profile.partials.update-password-form')
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
