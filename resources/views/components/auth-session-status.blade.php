@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-brand-700 dark:text-brand-400']) }}>
        {{ $status }}
    </div>
@endif
