<!DOCTYPE html>
<html lang="es">
<head>
    <title>Panel Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32x32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('favicon-16x16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/css/client.css', 'resources/js/app.js'])
</head>
<body>

    <nav class="app-nav">
        <a href="{{ route('client.dashboard') }}" class="app-nav-logo">
            @if(auth()->user()->gym?->logo_url)
                <img src="{{ auth()->user()->gym->logo_url }}" alt="{{ auth()->user()->gym->name }}" style="height:32px; object-fit:contain;">
            @else
                <img src="{{ asset('images/visionfit-logo-navbar.svg') }}" alt="VisionFit" style="height:26px; object-fit:contain; display:block;">
            @endif
        </a>

        <div class="app-nav-actions">
            <div class="app-nav-quick">
                @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                <a href="{{ route('client.notifications.index') }}" class="app-nav-link" title="Notificaciones" style="position:relative; color: var(--clr-text-muted);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:24px; height:24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    @if($unread)
                        <span class="app-nav-badge" style="position:absolute; top:-2px; right:-2px; background:var(--clr-primary); color:#fff; font-size:10px; border-radius:10px; padding:0 4px;">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
            </div>

            <div class="app-nav-right">
                <a href="{{ route('client.progress.index') }}" class="app-nav-user-btn" title="Mi progreso">
                    Progreso
                </a>
                <a href="{{ route('client.chat.index') }}" class="app-nav-user-btn" title="Chat con tu coach">
                    Chat
                </a>

                <div class="app-nav-account" id="navAccountWrap">
                    <button type="button" class="app-nav-user-btn" id="navAccountBtn" aria-expanded="false">
                        <span class="app-nav-user">{{ auth()->user()->name }}</span>
                        <svg class="app-nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <div class="app-nav-account-menu" id="navAccountMenu">
                        <a href="{{ route('client.account.edit') }}" class="app-nav-account-item">
                            Mi cuenta
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="app-nav-account-item danger" style="width:100%;">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Botón flotante del asistente IA, estilo burbuja de WhatsApp --}}
    <a href="{{ route('client.ai-chat.index') }}" class="ai-fab" title="Asistente IA">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
    </a>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const wrap = document.getElementById('navAccountWrap');
            const accBtn = document.getElementById('navAccountBtn');
            const menu = document.getElementById('navAccountMenu');
            if (wrap && accBtn && menu) {
                accBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const open = menu.classList.toggle('open');
                    accBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
                document.addEventListener('click', function (e) {
                    if (!wrap.contains(e.target)) {
                        menu.classList.remove('open');
                        accBtn.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });
    </script>

    <main class="app-main">
        {{ $slot }}
    </main>

    <x-client.bottom-nav />

</body>
</html>
