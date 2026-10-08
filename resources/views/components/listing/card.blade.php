@props(['vehicle'])

@php
    $cover = $vehicle->coverImage;
    $image = $cover?->path_800w ?: $cover?->path;
    $title = trim(implode(' ', array_filter([
        $vehicle->make?->name,
        $vehicle->model?->name,
        $vehicle->trim,
    ])));
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900']) }}>
    <a href="{{ route('vehicles.show', $vehicle) }}" class="block focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-600" aria-label="{{ $title }}">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
            @if ($image)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}"
                    alt="{{ $cover?->alt ?: $title }}"
                    loading="lazy"
                    decoding="async"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                >
            @else
                <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                    <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9 0h.375a.75.75 0 0 0 .75.75H18M6.75 14.25V11.25m10.5 3V11.25M4.375 18h15.25A1.125 1.125 0 0 0 20.7 16.875l-1.44-7.2A1.125 1.125 0 0 0 18.15 8.625H5.85a1.125 1.125 0 0 0-1.11 1.05l-1.44 7.2A1.125 1.125 0 0 0 4.375 18Z" />
                    </svg>
                </div>
            @endif

            <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                @if ($vehicle->is_featured)
                    <span class="inline-flex items-center gap-1 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-bold text-slate-900 shadow-sm">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.12 4.3 4.74.69c.47.07.66.65.32 1l-3.43 3.34.81 4.72c.08.47-.41.83-.83.61L12 15.98l-4.24 2.23c-.42.22-.91-.14-.83-.61l.81-4.72-3.43-3.34c-.34-.35-.15-.93.32-1l4.74-.69 2.11-4.3Z" /></svg>
                        {{ __('Featured') }}
                    </span>
                @endif

                @if ($vehicle->isSold())
                    <span class="inline-flex items-center rounded-full bg-slate-900/90 px-2.5 py-1 text-xs font-bold text-white">
                        {{ __('Sold') }}
                    </span>
                @endif
            </div>

            @if ($vehicle->condition)
                <span class="absolute right-3 top-3 inline-flex items-center rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:bg-slate-900/90 dark:text-slate-200">
                    {{ $vehicle->condition->label() }}
                </span>
            @endif
        </div>

        <div class="flex flex-1 flex-col p-4">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ $vehicle->formattedPrice }}
                </p>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $vehicle->year }}</p>
            </div>

            <h3 class="mt-1 truncate text-sm font-semibold text-slate-700 transition group-hover:text-brand-700 dark:text-slate-200 dark:group-hover:text-brand-400">
                {{ $title }}
            </h3>

            <dl class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1">
                    <dt class="sr-only">{{ __('Mileage') }}</dt>
                    <dd>{{ number_format($vehicle->mileage_km) }} km</dd>
                </div>
                <span aria-hidden="true" class="text-slate-300 dark:text-slate-600">&middot;</span>
                <div class="flex items-center gap-1">
                    <dt class="sr-only">{{ __('Fuel') }}</dt>
                    <dd>{{ $vehicle->fuelType?->name ?? '—' }}</dd>
                </div>
                <span aria-hidden="true" class="text-slate-300 dark:text-slate-600">&middot;</span>
                <div class="flex items-center gap-1">
                    <dt class="sr-only">{{ __('Transmission') }}</dt>
                    <dd>{{ $vehicle->transmission?->name ?? '—' }}</dd>
                </div>
            </dl>

            @if ($vehicle->location)
                <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                    <svg class="h-4 w-4 text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    {{ $vehicle->location }}
                </p>
            @endif
        </div>
    </a>

    @isset($favourite)
        <div class="absolute right-3 top-14">{{ $favourite }}</div>
    @endisset

    @isset($contactActions)
        <div class="border-t border-slate-100 p-3 dark:border-slate-800">
            {{ $contactActions }}
        </div>
    @endisset
</article>
