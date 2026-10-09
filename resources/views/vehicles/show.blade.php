@extends('layouts.storefront')

@section('title', ($vehicle->meta_title ?: $vehicle->make?->name.' '.$vehicle->model?->name.' '.$vehicle->year).' | '.Setting::get('site.name', 'Monaralk'))

@section('description', $vehicle->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($vehicle->description ?? ''), 155))

@section('canonical', route('vehicles.show', $vehicle))

@section('og:type', 'product')

@if ($vehicle->ogImage)
    @section('og:image', $vehicle->ogImage)
    @section('og:image:alt', $vehicle->listingTitle())
    @if ($vehicle->coverImage?->path_og)
        @section('og:image:width', 1200)
        @section('og:image:height', 630)
    @endif
@endif

@section('og:price:amount', number_format((float) $vehicle->price, 0, '.', ''))
@section('og:price:currency', 'LKR')

@php
    $conditionSchema = match ($vehicle->condition?->value) {
        'new' => 'https://schema.org/NewCondition',
        'certified_pre_owned' => 'https://schema.org/UsedCondition',
        default => 'https://schema.org/UsedCondition',
    };

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Car',
        'name' => $vehicle->listingTitle(),
        'url' => route('vehicles.show', $vehicle),
        'description' => $vehicle->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($vehicle->description ?? ''), 155),
        'sku' => $vehicle->slug,
        'brand' => filled($vehicle->make?->name) ? ['@type' => 'Brand', 'name' => $vehicle->make->name] : null,
        'model' => $vehicle->model?->name,
        'vehicleModelDate' => (string) $vehicle->year,
        'vehicleConfiguration' => $vehicle->trim,
        'bodyType' => $vehicle->bodyType?->name,
        'color' => $vehicle->exteriorColor?->name,
        'fuelType' => $vehicle->fuelType?->name,
        'vehicleTransmission' => $vehicle->transmission?->name,
        'itemCondition' => $conditionSchema,
        'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => (int) $vehicle->mileage_km, 'unitCode' => 'KMT'],
        'image' => $gallery->map(fn ($image) => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_1600w ?: $image->path))->all(),
        'offers' => [
            '@type' => 'Offer',
            'url' => route('vehicles.show', $vehicle),
            'priceCurrency' => 'LKR',
            'price' => number_format((float) $vehicle->price, 0, '.', ''),
            'availability' => $vehicle->isSold() ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
            'itemCondition' => $conditionSchema,
            'seller' => ['@type' => 'Organization', 'name' => Setting::get('site.name', 'Monaralk')],
        ],
    ], fn ($value) => $value !== null && $value !== '');
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('content')
    @php
        $title = trim(implode(' ', array_filter([
            $vehicle->make?->name,
            $vehicle->model?->name,
            $vehicle->trim,
        ])));

        $imageUrls = $gallery->map(fn ($image) => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_1600w ?: $image->path))->values();
        $imageAlts = $gallery->map(fn ($image) => $image->alt ?: $title)->values();

        $specRows = array_filter([
            __('Year') => $vehicle->year,
            __('Body type') => $vehicle->bodyType?->name,
            __('Mileage') => number_format($vehicle->mileage_km).' km',
            __('Condition') => $vehicle->condition?->label(),
            __('Transmission') => $vehicle->transmission?->name,
            __('Fuel') => $vehicle->fuelType?->name,
            __('Exterior colour') => $vehicle->exteriorColor?->name,
            __('Interior colour') => $vehicle->interiorColor?->name,
            __('Location') => $vehicle->location,
        ], fn ($value) => filled($value));

        $provenanceRows = array_filter([
            __('VIN') => $vehicle->vin,
            __('Registration') => $vehicle->registration_number,
            __('Owners') => $vehicle->owners_count,
            __('Accident history') => $vehicle->accident_history,
            __('Warranty') => $vehicle->warranty,
            __('Last serviced') => optional($vehicle->last_service_date)?->isoFormat('D MMM YYYY'),
        ], fn ($value) => filled($value));

        $financeRows = array_filter([
            __('Deposit') => $vehicle->finance_deposit !== null ? 'LKR '.number_format((float) $vehicle->finance_deposit) : null,
            __('Term') => $vehicle->finance_term_months ? $vehicle->finance_term_months.' '.__('months') : null,
            __('APR') => $vehicle->finance_apr !== null ? rtrim(rtrim(number_format((float) $vehicle->finance_apr, 2), '0'), '.').'%' : null,
        ], fn ($value) => filled($value));
    @endphp

    <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500 dark:text-slate-400">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="font-medium hover:text-brand-700 dark:hover:text-brand-400">{{ __('Home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('vehicles.index') }}" class="font-medium hover:text-brand-700 dark:hover:text-brand-400">{{ __('Browse cars') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-semibold text-slate-800 dark:text-slate-200">{{ $title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div x-data="gallery({{ $imageUrls->count() }}, {{ $imageUrls->toJson() }})" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-900">
                    <div class="relative aspect-[4/3]">
                        @forelse ($gallery as $index => $image)
                            <picture>
                                @if ($image->path_1600w_webp)
                                    <source type="image/webp" srcset="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_1600w_webp) }}">
                                @endif
                                <img
                                    x-show="active === {{ $index }}"
                                    x-transition.opacity.duration.200ms
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_1600w ?: $image->path) }}"
                                    alt="{{ $imageAlts[$index] }}"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    @if ($index !== 0) loading="lazy" @endif
                                >
                            </picture>
                        @empty
                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                <svg class="h-20 w-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9 0h.375a.75.75 0 0 0 .75.75H18M6.75 14.25V11.25m10.5 3V11.25M4.375 18h15.25A1.125 1.125 0 0 0 20.7 16.875l-1.44-7.2A1.125 1.125 0 0 0 18.15 8.625H5.85a1.125 1.125 0 0 0-1.11 1.05l-1.44 7.2A1.125 1.125 0 0 0 4.375 18Z" />
                                </svg>
                            </div>
                        @endforelse

                        @if ($imageUrls->count() > 1)
                            <button type="button" x-on:click="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-700 shadow transition hover:bg-white dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-900" aria-label="{{ __('Previous image') }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <button type="button" x-on:click="next()" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-700 shadow transition hover:bg-white dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-900" aria-label="{{ __('Next image') }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>

                            <span class="absolute bottom-3 right-3 rounded-full bg-slate-900/75 px-2.5 py-1 text-xs font-semibold text-white" x-text="(active + 1) + ' / {{ $imageUrls->count() }}'"></span>

                            <button type="button" x-on:click="open = true" class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-slate-900/75 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-900">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 20.25v-4.5m0 4.5h-4.5m4.5 0L15 15M20.25 3.75h-4.5m4.5 0v4.5m-4.5-4.5L15 9M3.75 20.25h4.5m-4.5 0v-4.5m0 4.5L9 15" /></svg>
                                {{ __('View full screen') }}
                            </button>
                        @endif
                    </div>

                    @if ($gallery->count() > 1)
                        <div class="flex gap-2 overflow-x-auto border-t border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                            @foreach ($gallery as $index => $image)
                                <button
                                    type="button"
                                    x-on:click="active = {{ $index }}"
                                    class="h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 transition"
                                    :class="active === {{ $index }} ? 'border-brand-600 opacity-100' : 'border-transparent opacity-60 hover:opacity-100'"
                                    aria-label="{{ __('Photo') }} {{ $index + 1 }}"
                                >
                                    <picture>
                                        @if ($image->path_800w_webp)
                                            <source type="image/webp" srcset="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_800w_webp) }}">
                                        @endif
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path_800w ?: $image->path) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    </picture>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div x-show="open" x-cloak x-transition x-on:keydown.escape.window="open = false" x-on:keydown.right.window="open && next()" x-on:keydown.left.window="open && prev()" class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4">
                        <button type="button" x-on:click="open = false" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" aria-label="{{ __('Close') }}">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>

                        @if ($imageUrls->count() > 1)
                            <button type="button" x-on:click="prev()" class="absolute left-4 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" aria-label="{{ __('Previous image') }}">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <button type="button" x-on:click="next()" class="absolute right-4 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" aria-label="{{ __('Next image') }}">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        @endif

                        <figure class="flex max-h-full max-w-5xl flex-col items-center gap-3">
                            <img :src="images[active]" class="max-h-[78vh] w-auto max-w-full rounded-lg object-contain" alt="{{ $title }}">
                            <figcaption class="text-sm text-white/70" x-text="(active + 1) + ' / {{ $imageUrls->count() }}'"></figcaption>
                        </figure>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-white">{{ $title }}</h1>

                                @if ($vehicle->is_featured)
                                    <span class="inline-flex items-center rounded-full bg-accent-500 px-2.5 py-1 text-xs font-bold text-slate-900">{{ __('Featured') }}</span>
                                @endif

                                @if ($vehicle->isSold())
                                    <span class="inline-flex items-center rounded-full bg-slate-800 px-2.5 py-1 text-xs font-bold text-white dark:bg-slate-700">{{ __('Sold') }}</span>
                                @endif
                            </div>

                            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                                <span>{{ $vehicle->year }}</span>
                                <span aria-hidden="true">&middot;</span>
                                <span>{{ number_format($vehicle->mileage_km) }} km</span>
                                <span aria-hidden="true">&middot;</span>
                                <span>{{ $vehicle->fuelType?->name ?? '—' }}</span>
                                <span aria-hidden="true">&middot;</span>
                                <span>{{ $vehicle->transmission?->name ?? '—' }}</span>
                                @if ($vehicle->location)
                                    <span aria-hidden="true">&middot;</span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4 text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                        {{ $vehicle->location }}
                                    </span>
                                @endif
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-3xl font-extrabold tracking-tight text-brand-700 dark:text-brand-400">{{ $vehicle->formattedPrice }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format($vehicle->views_count) }} {{ __('views') }}</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <x-listing.save-actions :vehicle="$vehicle" />
                    </div>

                    <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-4 border-t border-slate-100 pt-5 sm:grid-cols-2 dark:border-slate-800">
                        @foreach ($specRows as $label => $value)
                            <div class="flex items-baseline justify-between gap-4 border-b border-dashed border-slate-100 pb-2 dark:border-slate-800">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($vehicle->features->isNotEmpty())
                        <div class="mt-6">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Features') }}</h2>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($vehicle->features as $feature)
                                    <li class="rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800 ring-1 ring-inset ring-brand-600/20 dark:bg-brand-950 dark:text-brand-300">
                                        {{ $feature->name }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($provenanceRows !== [] || $financeRows !== [])
                        <div class="mt-6 grid gap-6 sm:grid-cols-2">
                            @if ($provenanceRows !== [])
                                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Provenance') }}</h2>
                                    <dl class="mt-3 space-y-2">
                                        @foreach ($provenanceRows as $label => $value)
                                            <div class="flex items-baseline justify-between gap-4">
                                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                                <dd class="text-xs font-semibold text-slate-900 dark:text-white">{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif

                            @if ($financeRows !== [])
                                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Finance') }}</h2>
                                    <dl class="mt-3 space-y-2">
                                        @foreach ($financeRows as $label => $value)
                                            <div class="flex items-baseline justify-between gap-4">
                                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                                <dd class="text-xs font-semibold text-slate-900 dark:text-white">{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if (filled($vehicle->description))
                        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Description') }}</h2>
                            <div class="mt-3 space-y-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{!! nl2br(e($vehicle->description)) !!}</div>
                        </div>
                    @endif
                </div>

                @if ($related->isNotEmpty())
                    <section class="mt-10">
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Similar cars') }}</h2>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            @foreach ($related as $item)
                                <x-listing.card :vehicle="$item" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Listing details') }}</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Status') }}</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $vehicle->status->label() }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Source') }}</dt>
                            <dd class="font-semibold capitalize text-slate-900 dark:text-white">{{ str_replace('_', ' ', $vehicle->source->value) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Listed') }}</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ ($vehicle->published_at ?? $vehicle->created_at)->diffForHumans() }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Views') }}</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ number_format($vehicle->views_count) }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('vehicles.spec-sheet', $vehicle) }}"
                       class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-brand-700 px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-950">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        {{ __('Download spec sheet') }}
                    </a>
                </div>

                <div x-data="financeCalculator" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Finance calculator') }}</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Estimate your monthly instalment.') }}</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="calc-price" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Price (LKR)') }}</label>
                            <input id="calc-price" type="number" min="0" step="100000" x-model.number="price" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="calc-deposit" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Deposit') }}</label>
                                <input id="calc-deposit" type="number" min="0" step="50000" x-model.number="deposit" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            </div>
                            <div>
                                <label for="calc-apr" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('APR %') }}</label>
                                <input id="calc-apr" type="number" min="0" max="40" step="0.1" x-model.number="apr" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            </div>
                        </div>

                        <div>
                            <label for="calc-term" class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <span>{{ __('Term (months)') }}</span>
                                <span x-text="term" class="text-brand-700 dark:text-brand-400"></span>
                            </label>
                            <input id="calc-term" type="range" min="12" max="84" step="12" x-model.number="term"
                                   class="w-full accent-brand-700">
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl bg-brand-50 p-4 text-center ring-1 ring-inset ring-brand-600/20 dark:bg-brand-950">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-400">{{ __('Monthly instalment') }}</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white" x-text="formatted"></p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Indicative only — not a finance offer.') }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Talk to the seller') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Ask about availability, service history or a test drive. Direct contact options appear here.') }}
                    </p>

                    <x-listing.contact-actions :vehicle="$vehicle" variant="hero" />

                    @include('partials.enquiry-form', ['vehicle' => $vehicle])

                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ number_format($vehicle->views_count) }} {{ __('people have viewed this car') }}
                    </p>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function gallery(count, images) {
            return {
                images: images || [],
                active: 0,
                open: false,
                next() { this.active = count > 0 ? (this.active + 1) % count : 0; },
                prev() { this.active = count > 0 ? (this.active - 1 + count) % count : 0; },
            };
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('financeCalculator', () => ({
                price: {{ (float) $vehicle->price }},
                deposit: {{ (int) round((float) ($vehicle->finance_deposit ?? $vehicle->price * 0.2)) }},
                apr: {{ (float) ($vehicle->finance_apr ?? Setting::get('finance.default_apr', 12.5)) }},
                term: {{ (int) ($vehicle->finance_term_months ?? Setting::get('finance.default_term_months', 60)) }},

                get monthly() {
                    const principal = Math.max(0, (this.price || 0) - (this.deposit || 0));
                    const months = Math.max(1, this.term || 1);
                    const rate = (Math.max(0, this.apr || 0) / 100) / 12;

                    if (rate === 0) return principal / months;

                    return principal * rate / (1 - Math.pow(1 + rate, -months));
                },

                get formatted() {
                    return 'LKR ' + Math.round(this.monthly).toLocaleString('en-US');
                },
            }));
        });
    </script>
@endpush
