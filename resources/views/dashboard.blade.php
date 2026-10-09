@extends('layouts.storefront')

@section('title', __('Dashboard').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Your Monaralk account at a glance — saved cars, submissions and listings.'))

@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-700 dark:text-brand-400">{{ __('Your account') }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Dashboard') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Welcome back, :name. Everything you have saved, submitted and listed lives here.', ['name' => $user->name]) }}
                </p>
            </div>

            <a href="{{ route('profile.edit') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-600 hover:text-brand-700 dark:border-slate-600 dark:text-slate-200 dark:hover:border-brand-500 dark:hover:text-brand-400">
                {{ __('Profile') }}
            </a>
        </div>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('saved.favourites') }}"
               class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-brand-600 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-500">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Favourites') }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-400">{{ $counts['favourite'] }}</p>
            </a>

            <a href="{{ route('saved.compare') }}"
               class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-brand-600 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-500">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Compare') }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-400">{{ $counts['compare'] }}</p>
            </a>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Submissions') }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $submissions->count() }}</p>
                @if ($pendingSubmissions > 0)
                    <p class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                        {{ __(':count pending review', ['count' => $pendingSubmissions]) }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('My listings') }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $listings->count() }}</p>
            </div>
        </div>

        @if ($isStaff)
            <a href="{{ url('/admin') }}"
               class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5 transition hover:border-brand-600 hover:bg-brand-100 dark:border-brand-900 dark:bg-brand-950 dark:hover:border-brand-500 dark:hover:bg-brand-900/60">
                <div>
                    <p class="text-lg font-bold text-brand-900 dark:text-brand-100">{{ __('Open admin panel') }}</p>
                    <p class="mt-1 text-sm text-brand-700 dark:text-brand-300">
                        {{ __('Manage vehicles, enquiries, submissions, users and settings.') }}
                    </p>
                </div>
                <span class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    {{ __('Admin') }}
                </span>
            </a>
        @endif

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <section aria-labelledby="submissions-heading">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="submissions-heading" class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Submissions') }}</h2>
                    <a href="{{ route('submit.create') }}" class="text-sm font-semibold text-brand-700 transition hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300">
                        {{ __('Sell your car') }}
                    </a>
                </div>

                @if ($submissions->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No submissions yet') }}</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Submit a car and our team will review it before it goes live.') }}
                        </p>
                        <a href="{{ route('submit.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                            {{ __('Sell your car') }}
                        </a>
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900">
                        @foreach ($submissions as $submission)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $submission->reference() }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $submission->created_at->translatedFormat('j M Y') }}
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $submission->status->value === 'pending',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $submission->status->value === 'approved',
                                    'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' => $submission->status->value === 'rejected',
                                    'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300' => $submission->status->value === 'needs_changes',
                                ])>
                                    {{ __($submission->status->label()) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="listings-heading">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="listings-heading" class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('My listings') }}</h2>
                    @if ($listings->count() > 0)
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __(':count cars', ['count' => $listings->count()]) }}</span>
                    @endif
                </div>

                @if ($listings->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No listings yet') }}</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Browse the marketplace or submit a car to get started.') }}
                        </p>
                        <a href="{{ route('vehicles.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                            {{ __('Browse cars') }}
                        </a>
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900">
                        @foreach ($listings as $listing)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    @if ($listing->status->value === 'published')
                                        <a href="{{ route('vehicles.show', $listing) }}" class="text-sm font-semibold text-slate-900 transition hover:text-brand-700 dark:text-white dark:hover:text-brand-400">
                                            {{ $listing->listingTitle() }}
                                        </a>
                                    @else
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $listing->listingTitle() }}</p>
                                    @endif
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $listing->formattedPrice }} @if ($listing->year) · {{ $listing->year }} @endif
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $listing->status->value === 'published',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $listing->status->value === 'pending',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $listing->status->value === 'draft',
                                    'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300' => $listing->status->value === 'sold',
                                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $listing->status->value === 'archived',
                                ])>
                                    {{ __($listing->status->label()) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
