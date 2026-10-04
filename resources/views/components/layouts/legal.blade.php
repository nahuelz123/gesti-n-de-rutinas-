<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name', 'VisionFit') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-neutral-950 text-neutral-100 antialiased">
    <header class="border-b border-neutral-800 px-5 py-4">
        <div class="mx-auto flex max-w-4xl items-center justify-between">
            <a href="{{ route('home') }}" class="font-bold tracking-wide text-white">VisionFit</a>
            <nav class="flex gap-4 text-sm text-neutral-300" aria-label="Información legal">
                <a href="{{ route('legal.privacy') }}" class="hover:text-white">Privacidad</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-white">Condiciones</a>
                <a href="{{ route('legal.cookies') }}" class="hover:text-white">Cookies</a>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-4xl px-5 py-8">
        <h1 class="mb-6 text-3xl font-bold">{{ $title }}</h1>
        <article class="space-y-5 leading-7 text-neutral-300">
            {{ $slot }}
        </article>
    </main>
    @include('partials.cookie-notice')
</body>
</html>
