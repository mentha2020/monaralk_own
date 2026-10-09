@extends('layouts.storefront')

@section('title', __('Compare cars').' | '.Setting::get('site.name', 'Monaralk'))

@section('description', __('Put up to :cap cars side by side and see the differences in one table.', ['cap' => $cap]))

@section('content')
    @php
        $rows = [
            __('Price') => fn ($vehicle) => $vehicle->formattedPrice,
            __('Year') => fn ($vehicle) => $vehicle->year,
            __('Mileage') => fn ($vehicle) => number_format($vehicle->mileage_km).' km',
            __('Condition') => fn ($vehicle) => $vehicle->condition?->label() ?? '—',
            __('Fuel') => fn ($vehicle) => $vehicle->fuelType?->name ?? '—',
            __('Transmission') => fn ($vehicle) => $vehicle->transmission?->name ?? '—',
            __('Body type') => fn ($vehicle) => $vehicle->bodyType?->name ?? '—',
            __('Exterior colour') => fn ($vehicle) => $vehicle->exteriorColor?->name ?? '—',
            __('Trim') => fn ($vehicle) => $vehicle->trim ?: '—',
            __('Location') => fn ($vehicle) => $vehicle->location ?: '—',
            __('Registration') => fn ($vehicle) => $vehicle->registration_number ?: '—',
            __('Previous owners') => fn ($vehicle) => $vehicle->owners_count ?? '—',
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-700 dark:text-brand-400">{{ __('Your account') }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('Compare cars') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Up to :cap cars, side by side. Differences are what matter — scan the rows, not the pages.', ['cap' => $cap]) }}
                </p>
            </div>

            <div class="flex items-center gap-3 text-sm font-semibold">
                <a href="{{ route('saved.favourites') }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-600 hover:text-brand-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-brand-500 dark:hover:text-brand-400">
                    {{ __('Favourites') }} <span class="tabular-nums">{{ $counts['favourite'] }}</span>
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-brand-800 dark:bg-brand-950 dark:text-brand-300">
                    {{ __('Compare') }} <span class="tabular-nums">{{ $counts['compare'] }}</span>
                </span>
            </div>
        </div>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($vehicles->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                <p class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Nothing to compare yet') }}</p>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Add up to :cap cars with the Compare button and they will line up here.', ['cap' => $cap]) }}
                </p>
                <a href="{{ route('vehicles.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    {{ __('Browse cars') }}
                </a>
            </div>
        @else
            <div class="mt-8 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="w-full min-w-[40rem] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th scope="col" class="w-40 px-4 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Spec') }}</th>
                            @foreach ($vehicles as $vehicle)
                                <th scope="col" class="px-4 py-4 align-bottom">
                                    <div class="flex flex-col items-start gap-2">
                                        <span class="block h-24 w-full overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                            @if ($vehicle->coverImage)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vehicle->coverImage->path_800w ?: $vehicle->coverImage->path) }}" alt="{{ $vehicle->listingTitle() }}" class="h-24 w-full object-cover" loading="lazy">
                                            @endif
                                        </span>
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="text-sm font-bold text-slate-900 transition hover:text-brand-700 dark:text-white dark:hover:text-brand-400">
                                            {{ $vehicle->listingTitle() }}
                                        </a>
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $vehicle->year }} · {{ number_format($vehicle->mileage_km) }} km</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($rows as $label => $value)
                            <tr class="border-b border-slate-100 dark:border-slate-800/70">
                                <th scope="row" class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</th>
                                @foreach ($vehicles as $vehicle)
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $value($vehicle) }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr>
                            <th scope="row" class="px-4 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Actions') }}</th>
                            @foreach ($vehicles as $vehicle)
                                <td class="px-4 py-4">
                                    <div class="flex flex-col items-start gap-2">
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-800">
                                            {{ __('View listing') }}
                                        </a>

                                        <form method="POST" action="{{ route('saved.destroy', [$vehicle, 'compare']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-900 hover:text-slate-900 dark:border-slate-600 dark:text-slate-300 dark:hover:border-white dark:hover:text-white">
                                                {{ __('Remove') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            @if (count($vehicles) >= $cap)
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('Compare is full. Remove a car to add another.') }}</p>
            @endif
        @endif
    </div>
@endsection
