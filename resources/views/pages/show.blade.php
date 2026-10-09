@extends('layouts.storefront')

@section('title', ($page->meta_title ?: $page->title).' | '.Setting::get('site.name', 'Monaralk'))

@section('description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 155))

@section('canonical', route('pages.show', $page))

@section('og:type', 'article')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ $page->title }}</h1>

        <div class="mt-6 space-y-5 text-base leading-7 text-slate-600 dark:text-slate-300">
            @foreach (preg_split("/\r?\n{2,}/", trim($page->content)) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="mt-10">
            <a href="{{ route('contact') }}" class="text-sm font-semibold text-brand-700 transition hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300">{{ __('Contact us') }} &rarr;</a>
        </div>
    </div>
@endsection
