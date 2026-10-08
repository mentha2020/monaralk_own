@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-500 text-start text-base font-medium text-brand-700 bg-brand-50 focus:outline-none focus:text-brand-800 focus:bg-brand-100 focus:border-brand-700 transition duration-150 ease-in-out dark:border-brand-400 dark:text-brand-300 dark:bg-brand-950/60 dark:focus:text-brand-200 dark:focus:bg-brand-950'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800 dark:hover:border-slate-600 dark:focus:text-slate-200 dark:focus:bg-slate-800 dark:focus:border-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
