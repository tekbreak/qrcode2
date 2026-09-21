@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'noindex' => false,
])

@php
    $seoTitle = $title ?: config('app.name');
    $seoDescription = $description ?: __('landing.meta_description');

    // Built from APP_URL rather than the request host so www/non-www and the
    // short-link domains all point search engines at the one canonical URL.
    $seoUrl = rtrim((string) config('app.url'), '/').request()->getPathInfo();
    $seoImage = $image ?: asset('images/og-image.png');
    $seoLocale = app()->getLocale() === 'es' ? 'es_ES' : 'en_US';
@endphp

<title>{{ $seoTitle }}</title>

@if($noindex)
    {{-- App surfaces carry no search or social metadata at all: nothing here is
         meant to be found, shared or previewed. No canonical either, since it
         would contradict the robots tag. --}}
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ $seoLocale }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
@endif

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
