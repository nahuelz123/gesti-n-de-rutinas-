<!DOCTYPE html>
<html lang="es">
<head>
    <title>Panel Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/css/client.css', 'resources/js/app.js'])
</head>
<body>

    <nav class="app-nav">
        <a href="{{ route('client.dashboard') }}" class="app-nav-logo" style="text-decoration:none;">
    @if(auth()->user()->gym?->logo)
        <img src="{{ auth()->user()->gym->logo }}" alt="{{ auth()->user()->gym->name }}" style="height:32px; object-fit:contain;">
    @else
        {{ auth()->user()->gym?->name ?? 'VisionFit' }}
    @endif
</a>
        <div class="app-nav-right">
            <span class="app-nav-user">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="app-nav-logout">Salir</button>
            </form>
        </div>
    </nav>

    <main class="app-main">
        {{ $slot }}
    </main>

</body>
</html>
