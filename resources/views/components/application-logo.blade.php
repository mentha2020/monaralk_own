@props(['wordmark' => false])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0" aria-hidden="true">
        <rect width="40" height="40" rx="11" class="fill-brand-600 dark:fill-brand-500" />
        <path d="M11 28V13L20 21.5L29 13V28" stroke="white" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    @if ($wordmark)
        <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Monaralk</span>
    @endif
</div>
