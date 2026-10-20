@props(['isDemo' => false, 'displayName' => 'Atleta'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>VisionFit · Tu entrenamiento en foco</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#080f1b">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="stylesheet" href="{{ asset('fonts/filament/filament/inter/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/visionfit/experience.css') }}">
    @livewireStyles
</head>
<body class="vf-shell">
    @php
        $homeUrl = $isDemo ? route('experience.demo') : route('client.dashboard');
        $profileUrl = $isDemo ? route('experience.demo.character') : route('client.character');
        $routineUrl = $isDemo ? route('experience.demo').'#vf-demo-lab' : route('client.routines.active');
    @endphp
    <a href="#vf-main-content" class="vf-skip-link">Ir al contenido</a>
    <header class="vf-app-header">
        <a class="vf-brand" href="{{ $homeUrl }}" aria-label="VisionFit · Inicio">
            <svg viewBox="0 0 40 40" width="34" height="34" fill="none" aria-hidden="true"><path d="m4 9 12 23h8L36 9h-9l-7 14-7-14H4Z" stroke="currentColor" stroke-width="2"/><path d="m13 9 7 14 7-14" stroke="currentColor" stroke-opacity=".35" stroke-width="2"/></svg>
            <span>VISION<span style="color:var(--vf-accent,#59dce9)">FIT</span></span>
        </a>
        <nav class="vf-main-nav" aria-label="Navegación principal">
            <a class="vf-nav-link" href="{{ $homeUrl }}" @if(request()->routeIs('experience.demo', 'client.dashboard')) aria-current="page" @endif>Inicio</a>
            <a class="vf-nav-link" href="{{ $routineUrl }}">Entrenamiento</a>
            <a class="vf-nav-link" href="{{ $profileUrl }}" @if(request()->routeIs('experience.demo.character', 'client.character')) aria-current="page" @endif>Mi personaje</a>
        </nav>
        @if($isDemo)
            <a class="vf-account-pill" href="{{ route('login') }}">Entrar a mi cuenta</a>
        @else
            <details class="vf-account-menu">
                <summary class="vf-account-pill">{{ \Illuminate\Support\Str::limit($displayName, 20) }} <span aria-hidden="true">⌄</span></summary>
                <nav aria-label="Opciones de cuenta">
                    <a href="{{ route('client.account.edit') }}">Mi cuenta</a>
                    <a href="{{ route('client.progress.index') }}">Medidas y progreso</a>
                    <a href="{{ route('client.nutrition.index') }}">Nutrición</a>
                    <a href="{{ route('client.recipes.index') }}">Recetas</a>
                    <a href="{{ route('client.chat.index') }}">Chat con mi entrenador</a>
                    <a href="{{ route('client.ai-chat.index') }}">Asistente IA</a>
                    <a href="{{ route('client.notifications.index') }}">Notificaciones</a>
                    <form method="POST" action="{{ route('logout') }}" data-push-logout>@csrf<button type="submit">Cerrar sesión</button></form>
                </nav>
            </details>
        @endif
    </header>
    <div id="vf-main-content">{{ $slot }}</div>
    <footer class="vf-experience-footer">
        @if($isDemo)
            <p>Versión de prueba · datos de ejemplo. Para ver tus rutinas y nutrición, <a href="{{ route('login') }}">iniciá sesión en el entorno de pruebas</a>.</p>
        @endif
        @include('partials.legal-links')
    </footer>
    <nav class="vf-mobile-nav" aria-label="Navegación móvil">
        <a href="{{ $homeUrl }}" @if(request()->routeIs('experience.demo', 'client.dashboard')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v11h-7v-7h-4v7H3V10Z"/></svg>Inicio</a>
        <a href="{{ $routineUrl }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-8z"/></svg>Rutina</a>
        <a href="{{ $profileUrl }}" @if(request()->routeIs('experience.demo.character', 'client.character')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4ZM8 6H4v3a4 4 0 0 0 4 4m8-7h4v3a4 4 0 0 1-4 4m-4 0v6m-4 2h8"/></svg>Personaje</a>
        <a href="{{ $isDemo ? route('login') : route('client.nutrition.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v6a3 3 0 0 0 6 0V3M8 3v18m10-18c-3 3-3 8 0 9h2V3h-2Zm2 9v9"/></svg>Nutrición</a>
    </nav>
    <script src="{{ asset('js/visionfit/experience.js') }}"></script>
    @unless($isDemo)<script src="{{ asset('js/visionfit/push-alerts.js') }}"></script>@endunless
    @livewireScripts
</body>
</html>
