@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand-500 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-brand-700 transition duration-150 ease-in-out dark:text-white dark:border-brand-400 dark:focus:border-brand-300'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out dark:text-slate-400 dark:hover:text-slate-200 dark:hover:border-slate-600 dark:focus:text-slate-200 dark:focus:border-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
