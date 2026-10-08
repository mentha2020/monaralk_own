<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            <span class="font-bold text-slate-900 dark:text-white">{{ number_format($vehicles->total()) }}</span>
            {{ \Illuminate\Support\Str::plural('vehicle', $vehicles->total()) }} {{ __('found') }}
        </p>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="toggleFilters"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 lg:hidden dark:border-slate-700 dark:text-slate-300 dark:hover:bg-white/5"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                {{ __('Filters') }}
            </button>

            <label for="vehicle-sort" class="sr-only">{{ __('Sort by') }}</label>
            <select
                id="vehicle-sort"
                wire:model.live="sort"
                class="rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
            >
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="mt-4 flex flex-wrap items-center gap-2">
            @foreach ($activeFilters as $chip)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 py-1 pl-3 pr-1.5 text-xs font-medium text-brand-800 ring-1 ring-inset ring-brand-600/20 dark:bg-brand-950 dark:text-brand-300">
                    <span class="text-brand-600/70 dark:text-brand-400/70">{{ $chip['label'] }}:</span>
                    <span class="font-semibold">{{ $chip['value'] }}</span>
                    <button
                        type="button"
                        wire:click="clearFilter('{{ $chip['property'] }}')"
                        class="inline-flex h-5 w-5 items-center justify-center rounded-full text-brand-600 transition hover:bg-brand-600 hover:text-white dark:text-brand-400"
                        aria-label="{{ __('Remove filter') }}"
                    >
                        &times;
                    </button>
                </span>
            @endforeach

            <button
                type="button"
                wire:click="clearAll"
                class="ml-1 text-xs font-semibold text-slate-500 underline underline-offset-2 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-white"
            >
                {{ __('Clear all') }}
            </button>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[17rem_1fr]">
        <aside
            :class="showFilters ? 'block' : 'hidden'"
            class="rounded-2xl border border-slate-200 bg-white p-5 lg:block dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Refine search') }}</h2>
                @if ($activeFilters !== [])
                    <button type="button" wire:click="clearAll" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-400">
                        {{ __('Reset') }}
                    </button>
                @endif
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="filter-keyword" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Keyword') }}</label>
                    <input
                        id="filter-keyword"
                        type="search"
                        wire:model.live.debounce.400ms="keyword"
                        placeholder="{{ __('e.g. Axio, low mileage') }}"
                        class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                </div>

                <div>
                    <label for="filter-make" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Make') }}</label>
                    <select id="filter-make" wire:model.live="make_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any make') }}</option>
                        @foreach ($makes as $make)
                            <option value="{{ $make->id }}">{{ $make->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-model" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Model') }}</label>
                    <select id="filter-model" wire:model.live="model_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ filled($make_id) ? __('Any model') : __('Select a make first') }}</option>
                        @foreach ($models as $model)
                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-condition" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Condition') }}</label>
                    <select id="filter-condition" wire:model.live="condition" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any condition') }}</option>
                        @foreach ($conditions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="filter-min-price" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Min price') }}</label>
                        <input id="filter-min-price" type="number" min="0" step="100000" wire:model.live.debounce.600ms="min_price"
                               class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    </div>
                    <div>
                        <label for="filter-max-price" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Max price') }}</label>
                        <input id="filter-max-price" type="number" min="0" step="100000" wire:model.live.debounce.600ms="max_price"
                               class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="filter-min-year" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Year from') }}</label>
                        <input id="filter-min-year" type="number" min="{{ $yearBounds['min'] }}" max="{{ $yearBounds['max'] }}" wire:model.live.debounce.600ms="min_year"
                               class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    </div>
                    <div>
                        <label for="filter-max-year" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Year to') }}</label>
                        <input id="filter-max-year" type="number" min="{{ $yearBounds['min'] }}" max="{{ $yearBounds['max'] }}" wire:model.live.debounce.600ms="max_year"
                               class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    </div>
                </div>

                <div>
                    <label for="filter-mileage" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Max mileage (km)') }}</label>
                    <input id="filter-mileage" type="number" min="0" step="10000" wire:model.live.debounce.600ms="max_mileage"
                           class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                </div>

                <div>
                    <label for="filter-body" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Body type') }}</label>
                    <select id="filter-body" wire:model.live="body_type_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any body type') }}</option>
                        @foreach ($bodyTypes as $bodyType)
                            <option value="{{ $bodyType->id }}">{{ $bodyType->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-fuel" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Fuel') }}</label>
                    <select id="filter-fuel" wire:model.live="fuel_type_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any fuel') }}</option>
                        @foreach ($fuelTypes as $fuelType)
                            <option value="{{ $fuelType->id }}">{{ $fuelType->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-transmission" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Transmission') }}</label>
                    <select id="filter-transmission" wire:model.live="transmission_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any transmission') }}</option>
                        @foreach ($transmissions as $transmission)
                            <option value="{{ $transmission->id }}">{{ $transmission->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-color" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Exterior colour') }}</label>
                    <select id="filter-color" wire:model.live="exterior_color_id" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Any colour') }}</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-location" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Location') }}</label>
                    <select id="filter-location" wire:model.live="location" class="block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">{{ __('Anywhere') }}</option>
                        @foreach ($locations as $place)
                            <option value="{{ $place }}">{{ $place }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex cursor-pointer items-center gap-2 border-t border-slate-100 pt-4 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                    <input type="checkbox" wire:model.live="featured" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600 dark:border-slate-600 dark:bg-slate-900">
                    {{ __('Featured only') }}
                </label>

                <button
                    type="button"
                    wire:click="toggleFilters"
                    class="w-full rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800 lg:hidden"
                >
                    {{ __('Show results') }}
                </button>
            </div>
        </aside>

        <div>
            @if ($vehicles->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                    </svg>
                    <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">{{ __('No vehicles match those filters') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Try widening the price or year range.') }}</p>
                    <button type="button" wire:click="clearAll"
                            class="mt-5 inline-flex rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800">
                        {{ __('Clear all filters') }}
                    </button>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($vehicles as $vehicle)
                        <x-listing.card :vehicle="$vehicle" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $vehicles->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
