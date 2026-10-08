@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-600 focus:ring-brand-600 rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500']) }}>
