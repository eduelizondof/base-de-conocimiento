<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @php
            $pageTitle = $title ?? config('app.name');
            $pageDescription = $metaDescription ?? 'Portal de consulta y gestión documental de la Secretaría de Educación Jalisco (SEJ).';
            $pageKeywords = $metaKeywords ?? 'SEJ, educación, Jalisco, base de conocimiento, documentos';
            $canonicalUrl = url()->current();
            $ogImage = $ogImage ?? asset('img/logo_blanco_educacion.svg');
            $pageOgImage = \Illuminate\Support\Str::startsWith((string) $ogImage, ['http://', 'https://'])
                ? $ogImage
                : url($ogImage);
        @endphp

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="keywords" content="{{ $pageKeywords }}">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#e9004c">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <meta property="og:type" content="website">
        <meta property="og:locale" content="es_MX">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:image" content="{{ $pageOgImage }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $pageOgImage }}">

        <link rel="icon" href="{{ asset('favicon-blanco/favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('favicon-blanco/favicon.svg') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
