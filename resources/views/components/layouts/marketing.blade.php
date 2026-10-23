@props(['meta', 'noindex' => false])
@php
    $canonical = rtrim(config('commerce.public_url'), '/').($meta['path'] ?? '/');
    $structured = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $meta['title'], 'description' => $meta['description'], 'url' => $canonical, 'inLanguage' => 'es-AR', 'isPartOf' => ['@type' => 'WebSite', 'name' => 'VisionFit', 'url' => rtrim(config('commerce.public_url'), '/').'/']];
@endphp
<!doctype html>
<html lang="es-AR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] }}</title><meta name="description" content="{{ $meta['description'] }}">
    <meta name="robots" content="{{ $noindex || !config('commerce.indexable') ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website"><meta property="og:locale" content="es_AR"><meta property="og:site_name" content="VisionFit">
    <meta property="og:title" content="{{ $meta['title'] }}"><meta property="og:description" content="{{ $meta['description'] }}"><meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ rtrim(config('commerce.public_url'), '/') }}/images/visionfit-social.png">
    <meta name="twitter:card" content="summary_large_image"><meta name="theme-color" content="#141414">
    @if(config('commerce.search_console_verification'))<meta name="google-site-verification" content="{{ config('commerce.search_console_verification') }}">@endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/visionfit/marketing.css') }}?v={{ filemtime(public_path('css/visionfit/marketing.css')) }}">
    <script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</head>
<body class="vf-marketing">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div style="background:#262323;color:#e8dddd;font-size:11px;padding:7px 18px;display:flex;justify-content:center;gap:20px;flex-wrap:wrap"><a href="{{ url('/baja') }}">Botón de baja de servicio</a><a href="{{ url('/arrepentimiento') }}">Botón de arrepentimiento</a></div>
<header class="m-header"><div class="m-wrap m-nav">
    <a href="{{ route('home') }}" class="m-brand" aria-label="VisionFit, inicio"><img src="{{ asset('images/visionfit-logo-navbar.svg') }}" alt="VisionFit" width="151" height="36"></a>
    <nav class="m-desktop-nav" aria-label="Principal"><a href="{{ url('/asesorias-online') }}">Asesorías</a><a href="{{ url('/software-entrenadores-personales') }}">Entrenadores</a><a href="{{ url('/software-gimnasios') }}">Gimnasios</a><a href="{{ url('/planes') }}">Planes</a><a href="{{ url('/recursos') }}">Recursos</a></nav>
    <a class="m-button m-button-small m-button-outline" href="{{ route('login') }}">Entrar a la app <span aria-hidden="true">↗</span></a>
    <details class="m-mobile-menu"><summary>Menú</summary><nav aria-label="Principal móvil"><a href="{{ url('/asesorias-online') }}">Asesorías</a><a href="{{ url('/software-entrenadores-personales') }}">Entrenadores</a><a href="{{ url('/software-gimnasios') }}">Gimnasios</a><a href="{{ url('/planes') }}">Planes</a><a href="{{ url('/recursos') }}">Recursos</a><a href="{{ url('/contacto') }}">Contacto</a></nav></details>
</div></header>
<main id="contenido">{{ $slot }}</main>
<footer class="m-footer"><div class="m-wrap"><div class="m-footer-grid"><div><a class="m-wordmark" href="{{ route('home') }}">VISION<span>FIT</span></a><p>Un plan claro.<br>Tu progreso, a la vista.</p><p class="m-small">Entrenamiento online y herramientas para profesionales en Argentina.</p></div><div><strong>Descubrí VisionFit</strong><a href="{{ url('/asesorias-online') }}">Asesorías online</a><a href="{{ url('/software-entrenadores-personales') }}">Para entrenadores</a><a href="{{ url('/software-gimnasios') }}">Para gimnasios</a><a href="{{ url('/planes') }}">Planes y precios</a></div><div><strong>Te acompañamos</strong><a href="{{ url('/contacto') }}">Contacto y demo</a><a href="{{ url('/preguntas-frecuentes') }}">Preguntas frecuentes</a><a href="{{ url('/recursos') }}">Recursos</a><a href="{{ route('login') }}">Entrar a mi cuenta</a></div><div><strong>Información y derechos</strong><a href="{{ route('legal.privacy') }}">Privacidad</a><a href="{{ route('legal.cookies') }}">Cookies</a><a href="{{ route('legal.terms') }}">Condiciones</a><a href="{{ url('/baja') }}">Botón de baja de servicio</a><a href="{{ url('/arrepentimiento') }}">Botón de arrepentimiento</a><a href="{{ url('/privacidad/solicitud') }}">Consultas sobre mis datos</a></div></div><div class="m-footer-bottom"><span>© {{ date('Y') }} VisionFit.</span><span>Hecho para acompañar el trabajo del profesional.</span></div></div></footer>
@include('partials.cookie-notice')
</body></html>
