<nav class="client-bottom-nav">
    @php
        $currentRoute = request()->route()->getName();
    @endphp

    <a href="{{ route('client.dashboard') }}" class="bottom-nav-item {{ $currentRoute === 'client.dashboard' ? 'active' : '' }}" aria-label="Inicio">
        <svg class="bottom-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.592 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        <span class="bottom-nav-label">Inicio</span>
    </a>

    <a href="{{ route('client.routines.active') }}" class="bottom-nav-item {{ Str::startsWith($currentRoute, 'client.routines') ? 'active' : '' }}" aria-label="Rutina">
        <svg class="bottom-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
        </svg>
        <span class="bottom-nav-label">Rutina</span>
    </a>

    <a href="{{ route('client.nutrition.index') }}" class="bottom-nav-item {{ Str::startsWith($currentRoute, 'client.nutrition') ? 'active' : '' }}" aria-label="Dieta">
        <svg class="bottom-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
        </svg>
        <span class="bottom-nav-label">Dieta</span>
    </a>

    <button type="button" class="bottom-nav-item" id="btnMoreMenu" aria-label="Más" aria-expanded="false" aria-controls="bottomSheetMore">
        <svg class="bottom-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
        <span class="bottom-nav-label">Más</span>
    </button>
</nav>

<div class="bottom-sheet-backdrop" id="bottomSheetBackdrop"></div>
<div class="client-bottom-sheet" id="bottomSheetMore">
    <div style="width:40px; height:4px; background:var(--clr-border); border-radius:4px; margin:0 auto 20px auto;"></div>
    
    <div style="display:flex; flex-direction:column; gap:8px;">
        <a href="{{ route('client.progress.index') }}" class="app-nav-account-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            Mi Progreso
        </a>
        <a href="{{ route('client.recipes.index') }}" class="app-nav-account-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            Catálogo de Recetas
        </a>
        <a href="{{ route('client.chat.index') }}" class="app-nav-account-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-.72 0-1.437-.023-2.148-.069m8.148-6.573V6.75c0-1.108-.806-2.057-1.907-2.185a48.507 48.507 0 0 0-11.186 0c-1.1.128-1.907 1.077-1.907 2.185v4.286c0 1.108.806 2.057 1.907 2.185.106.012.213.023.32.033m9.166-4.5H3.75"/></svg>
            Chat con Coach
        </a>
        <a href="{{ route('client.ai-chat.index') }}" class="app-nav-account-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
            Asistente IA
        </a>
        <div style="height:1px; background:var(--clr-border); margin:4px 0;"></div>
        <a href="{{ route('client.account.edit') }}" class="app-nav-account-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            Mi Cuenta
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="app-nav-account-item danger" style="width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 -3h12m0 0-3-3m3 3-3 3"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btnMore = document.getElementById('btnMoreMenu');
        const sheet = document.getElementById('bottomSheetMore');
        const backdrop = document.getElementById('bottomSheetBackdrop');
        if (!btnMore || !sheet || !backdrop) return;

        function closeSheet() {
            sheet.classList.remove('open');
            backdrop.classList.remove('open');
            btnMore.setAttribute('aria-expanded', 'false');
            btnMore.classList.remove('active');
            document.body.style.overflow = '';
        }

        btnMore.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = sheet.classList.contains('open');
            if (isOpen) {
                closeSheet();
            } else {
                sheet.classList.add('open');
                backdrop.classList.add('open');
                btnMore.setAttribute('aria-expanded', 'true');
                btnMore.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });

        backdrop.addEventListener('click', closeSheet);
    });
</script>
