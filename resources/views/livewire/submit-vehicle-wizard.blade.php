<div class="mx-auto max-w-3xl">
    @if ($step === 6)
        <div class="rounded-2xl border border-brand-200 bg-brand-50 p-8 text-center dark:border-brand-900 dark:bg-brand-950">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-700 text-white">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <h2 class="mt-4 text-2xl font-extrabold text-slate-900 dark:text-white">{{ __('Submission received') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('Thank you. Our team reviews every submission by hand — you will get an email once it is approved, or if we need anything else.') }}
            </p>
            @if ($reference)
                <p class="mt-4 inline-block rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-brand-600/30 dark:bg-slate-900 dark:text-white">
                    {{ __('Reference') }}: {{ $reference }}
                </p>
            @endif
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('vehicles.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">{{ __('Browse cars') }}</a>
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-slate-900 px-5 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-900 hover:text-white dark:border-white dark:text-white dark:hover:bg-white dark:hover:text-slate-900">{{ __('Back to home') }}</a>
            </div>
        </div>
    @else

    <input type="text" wire:model="website" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="pointer-events-none absolute -left-[9999px] h-px w-px border-0 p-0 opacity-0">

    <div class="mb-8">
        <ol class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            @foreach ($steps as $number => $label)
                <li class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 {{ $step === $number ? 'bg-brand-700 text-white' : ($step > $number ? 'bg-brand-100 text-brand-800 dark:bg-brand-950 dark:text-brand-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400') }}">
                        <span>{{ $step > $number ? '✓' : $number }}</span>
                        <span>{{ $label }}</span>
                    </span>
                    @if (! $loop->last)
                        <span class="text-slate-300 dark:text-slate-600">—</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        @if ($step === 1)
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Tell us about your car') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Step 1 of 5 — the basics. You can review everything before it is sent.') }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="make" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Make') }}</label>
                    <select id="make" wire:model.live="form.make_id" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ __('Select a make') }}</option>
                        @foreach ($makes as $make)
                            <option value="{{ $make->id }}">{{ $make->name }}</option>
                        @endforeach
                    </select>
                    @error('form.make_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="model" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Model') }}</label>
                    <select id="model" wire:model="form.model_id" @disabled(! $form['make_id']) class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ $form['make_id'] ? __('Select a model') : __('Pick a make first') }}</option>
                        @foreach ($models as $model)
                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                        @endforeach
                    </select>
                    @error('form.model_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="year" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Year') }}</label>
                    <input id="year" type="number" min="1950" max="2030" wire:model="form.year" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.year')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="condition" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Condition') }}</label>
                    <select id="condition" wire:model="form.condition" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="used">{{ __('Used') }}</option>
                        <option value="new">{{ __('Brand new') }}</option>
                    </select>
                    @error('form.condition')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="location" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Location') }}</label>
                    <input id="location" type="text" wire:model="form.location" placeholder="{{ __('City or district') }}" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200 dark:placeholder:text-slate-500">
                    @error('form.location')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Description') }}</label>
                    <textarea id="description" rows="5" wire:model="form.description" placeholder="{{ __('Service history, upgrades, reasons for selling…') }}" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200 dark:placeholder:text-slate-500"></textarea>
                    @error('form.description')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        @if ($step === 2)
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Specs and price') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Step 2 of 5 — numbers buyers filter on. Price is in Sri Lankan Rupees.') }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="mileage" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Mileage (km)') }}</label>
                    <input id="mileage" type="number" min="0" step="1" wire:model="form.mileage_km" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.mileage_km')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="price" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Price (LKR)') }}</label>
                    <input id="price" type="number" min="100000" step="1000" wire:model="form.price" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.price')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="transmission" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Transmission') }}</label>
                    <select id="transmission" wire:model="form.transmission_id" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($transmissions as $transmission)
                            <option value="{{ $transmission->id }}">{{ $transmission->name }}</option>
                        @endforeach
                    </select>
                    @error('form.transmission_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fuel" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Fuel') }}</label>
                    <select id="fuel" wire:model="form.fuel_type_id" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($fuelTypes as $fuelType)
                            <option value="{{ $fuelType->id }}">{{ $fuelType->name }}</option>
                        @endforeach
                    </select>
                    @error('form.fuel_type_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="body" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Body type') }}</label>
                    <select id="body" wire:model="form.body_type_id" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ __('Optional') }}</option>
                        @foreach ($bodyTypes as $bodyType)
                            <option value="{{ $bodyType->id }}">{{ $bodyType->name }}</option>
                        @endforeach
                    </select>
                    @error('form.body_type_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="color" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Exterior colour') }}</label>
                    <select id="color" wire:model="form.exterior_color_id" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                        <option value="">{{ __('Optional') }}</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                        @endforeach
                    </select>
                    @error('form.exterior_color_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="trim" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Trim') }}</label>
                    <input id="trim" type="text" wire:model="form.trim" placeholder="{{ __('G, GT, EX…') }}" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200 dark:placeholder:text-slate-500">
                    @error('form.trim')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="registration" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Registration number') }}</label>
                    <input id="registration" type="text" wire:model="form.registration_number" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.registration_number')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="owners" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Previous owners') }}</label>
                    <input id="owners" type="number" min="1" max="10" wire:model="form.owners_count" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.owners_count')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        @if ($step === 3)
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Add photos') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Step 3 of 5 — up to :count photos, 5 MB each. JPG, PNG or WebP.', ['count' => \App\Livewire\SubmitVehicleWizard::MAX_PHOTOS]) }}</p>

            <label for="photos" class="mt-6 flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-500 bg-slate-50 px-6 py-8 text-center transition hover:border-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:hover:border-brand-500">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Choose photos') }}</span>
                <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Drag and drop works too') }}</span>
                <input id="photos" type="file" accept="image/jpeg,image/png,image/webp" multiple wire:model="photos" class="sr-only">
            </label>

            <div wire:loading.class="opacity-60" wire:target="photos">
                <p wire:loading.remove wire:target="photos" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Photos upload as you add them.') }}</p>
                <p wire:loading wire:target="photos" class="mt-2 text-xs font-semibold text-brand-700 dark:text-brand-400">{{ __('Uploading…') }}</p>
            </div>

            @foreach ($errors->keys() as $errorKey)
                @if ($errorKey === 'photos' || str_starts_with($errorKey, 'photos.'))
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $errors->first($errorKey) }}</p>
                @endif
            @endforeach

            @if ($photos !== [])
                <ul class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($photos as $index => $photo)
                        <li class="relative overflow-hidden rounded-xl ring-1 ring-slate-200 dark:ring-slate-700">
                            @if ($this->photoPreviewUrl($photo))
                                <img src="{{ $this->photoPreviewUrl($photo) }}" alt="" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover">
                            @else
                                <div class="aspect-[4/3] w-full bg-slate-100 dark:bg-slate-800"></div>
                            @endif
                            <button type="button" wire:click="removePhoto({{ $index }})" class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-900" aria-label="{{ __('Remove photo') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                            @if ($index === 0)
                                <span class="absolute bottom-2 left-2 rounded-full bg-brand-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">{{ __('Cover') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

        @if ($step === 4)
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('How can we reach you?') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Step 4 of 5 — our team contacts you before anything goes public.') }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="submit-name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Name') }}</label>
                    <input id="submit-name" type="text" wire:model="form.name" autocomplete="name" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="submit-email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email') }}</label>
                    <input id="submit-email" type="email" wire:model="form.email" autocomplete="email" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="submit-phone" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Phone') }} <span class="font-normal normal-case tracking-normal">({{ __('optional') }})</span></label>
                    <input id="submit-phone" type="tel" wire:model="form.phone" autocomplete="tel" class="block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-950 dark:text-slate-200">
                    @error('form.phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        @if ($step === 5)
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Review and submit') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Step 5 of 5 — check everything is right. You can go back and edit any step.') }}</p>

            <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ($summary as $row)
                    <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $row['label'] }}</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $row['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Description') }}</p>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $form['description'] }}</p>
            </div>

            @if ($photos !== [])
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Photos') }} ({{ count($photos) }})</p>
                    <ul class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-5">
                        @foreach ($photos as $photo)
                            <li class="overflow-hidden rounded-lg ring-1 ring-slate-200 dark:ring-slate-700">
                                @if ($this->photoPreviewUrl($photo))
                                    <img src="{{ $this->photoPreviewUrl($photo) }}" alt="" loading="lazy" decoding="async" class="aspect-square w-full object-cover">
                                @else
                                    <div class="aspect-square w-full bg-slate-100 dark:bg-slate-800"></div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-950 dark:text-slate-300">
                <p class="font-semibold text-slate-900 dark:text-white">{{ __('Contact') }}</p>
                <p class="mt-1">{{ $form['name'] }} · {{ $form['email'] }}@if (filled($form['phone'])) · {{ $form['phone'] }}@endif</p>
            </div>
        @endif

        <div class="mt-8 flex items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
            <button type="button" wire:click="back" @disabled($step === 1) class="inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-slate-500 px-5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-400 dark:text-slate-200 dark:hover:border-white dark:hover:text-white">
                {{ __('Back') }}
            </button>

            @if ($step < 5)
                <button type="button" wire:click="next" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-6 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950">
                    {{ __('Continue') }}
                </button>
            @else
                <button type="button" wire:click="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-6 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950">
                    {{ __('Submit for review') }}
                </button>
            @endif
        </div>
    </div>
    @endif
</div>
