@php
    $siteName = Setting::get('site.name', 'Monaralk');
    $tagline = Setting::get('site.tagline', "Sri Lanka's trusted car marketplace");
    $title = $__env->yieldContent('title', $siteName);
    $description = $__env->yieldContent('description', $tagline);
    $canonical = $__env->yieldContent('canonical', url()->current());
    $robots = $__env->yieldContent('robots', 'index, follow, max-image-preview:large, max-snippet:-1');
    $ogType = $__env->yieldContent('og:type', 'website');
    $hasImage = $__env->hasSection('og:image');
@endphp

<title>{!! $title !!}</title>
<meta name="description" content="{!! $description !!}">
<link rel="canonical" href="{!! $canonical !!}">
<meta name="robots" content="{!! $robots !!}">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'si' ? 'si_LK' : 'en_LK' }}">
<meta property="og:type" content="{!! $ogType !!}">
<meta property="og:title" content="{!! $title !!}">
<meta property="og:description" content="{!! $description !!}">
<meta property="og:url" content="{!! $canonical !!}">

@if ($hasImage)
    <meta property="og:image" content="{!! $__env->yieldContent('og:image') !!}">
    @hasSection('og:image:alt')
        <meta property="og:image:alt" content="{!! $__env->yieldContent('og:image:alt') !!}">
    @endif
    @hasSection('og:image:width')
        <meta property="og:image:width" content="{!! $__env->yieldContent('og:image:width') !!}">
        <meta property="og:image:height" content="{!! $__env->yieldContent('og:image:height') !!}">
    @endif
@endif

@hasSection('og:price:amount')
    <meta property="product:price:amount" content="{!! $__env->yieldContent('og:price:amount') !!}">
    <meta property="product:price:currency" content="{!! $__env->yieldContent('og:price:currency', 'LKR') !!}">
@endif

<meta name="twitter:card" content="{{ $hasImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{!! $title !!}">
<meta name="twitter:description" content="{!! $description !!}">
@if ($hasImage)
    <meta name="twitter:image" content="{!! $__env->yieldContent('og:image') !!}">
@endif
