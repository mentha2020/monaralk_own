<div class="flex flex-col gap-2" wire:key="save-{{ $vehicleId }}">
    <div class="flex items-center gap-2">
        <button
            type="button"
            wire:click="toggleFavourite"
            x-data="{ on: $wire.entangle('favourite') }"
            x-on:click="on = !on"
            :aria-pressed="on ? 'true' : 'false'"
            :class="on ? 'border-brand-700 bg-brand-700 text-white' : 'border-white/70 bg-white/95 text-slate-700'"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm transition hover:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-900/95 dark:text-slate-200 dark:focus:ring-offset-slate-950"
            aria-label="{{ __('Save to favourites') }}"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            <span x-text="on ? '{{ __('Saved') }}' : '{{ __('Save') }}'">{{ __('Save') }}</span>
        </button>

        <button
            type="button"
            wire:click="toggleCompare"
            x-data="{ on: $wire.entangle('compare') }"
            x-on:click="on = !on"
            :aria-pressed="on ? 'true' : 'false'"
            :class="on ? 'border-slate-900 bg-slate-900 text-white' : 'border-white/70 bg-white/95 text-slate-700'"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm transition hover:border-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-600 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-900/95 dark:text-slate-200 dark:focus:ring-offset-slate-950"
            aria-label="{{ __('Add to compare') }}"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 3h5v5M8 3H3v5M21 3l-7 7M3 3l7 7M16 21h5v-5M8 21H3v-5M21 21l-7-7M3 21l7-7" />
            </svg>
            <span x-text="on ? '{{ __('Comparing') }}' : '{{ __('Compare') }}'">{{ __('Compare') }}</span>
        </button>
    </div>

    @if ($notice)
        <p role="status" class="text-xs font-semibold text-amber-700 dark:text-amber-400">{{ $notice }}</p>
    @endif
</div>
