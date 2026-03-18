<!DOCTYPE html>
<html lang="es">
<head>
    <title>Panel Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/css/client.css', 'resources/js/app.js'])
</head>
<body>

    <nav class="app-nav">
        <div class="app-nav-logo">Gym App</div>
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