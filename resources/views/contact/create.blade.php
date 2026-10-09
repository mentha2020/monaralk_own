@extends('layouts.storefront')

@section('title', __('Contact us').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Questions about a listing, a sale or your account? Send us a message and we will get back to you within one working day.'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Contact us') }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                    {{ __('Questions about a listing, a sale or your account? Send us a message and we will get back to you within one working day.') }}
                </p>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    @include('partials.enquiry-form')
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Reach us directly') }}</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        @if ($phone = Setting::get('contact.phone'))
                            <li>
                                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Phone') }}</span>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ $phone }}</a>
                            </li>
                        @endif
                        @if ($whatsapp = Setting::get('contact.whatsapp'))
                            <li>
                                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">WhatsApp</span>
                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" target="_blank" rel="noopener noreferrer nofollow" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ $whatsapp }}</a>
                            </li>
                        @endif
                        @if ($email = Setting::get('contact.email'))
                            <li>
                                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email') }}</span>
                                <a href="mailto:{{ $email }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">{{ $email }}</a>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Selling a car?') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('List it yourself in a few minutes. Photos, specs and price — our team reviews every submission before it goes live.') }}
                    </p>
                    <a href="{{ route('submit.create') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-slate-900 px-5 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-900 hover:text-white dark:border-white dark:text-white dark:hover:bg-white dark:hover:text-slate-900">
                        {{ __('Submit your car') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
