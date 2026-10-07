<x-layouts.auth>
    <style>
        .vf-login-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 24px;
            padding: 28px;
            background:
                radial-gradient(circle at 100% 0%, rgba(230,57,70,.16), transparent 34%),
                linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.015));
            box-shadow: 0 24px 70px rgba(0,0,0,.38);
        }
        .vf-login-brand { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
        .vf-login-mark {
            width:46px; height:46px; border-radius:14px; display:grid; place-items:center;
            background:#e63946; box-shadow:0 10px 28px rgba(230,57,70,.28);
        }
        .vf-login-brand strong { display:block; color:#fff; font-size:20px; line-height:1; letter-spacing:-.03em; }
        .vf-login-brand span {
            display:block; margin-top:5px; color:#737373; font-size:10px; font-weight:800;
            letter-spacing:.14em; text-transform:uppercase;
        }
        .vf-login-copy { margin-bottom:24px; }
        .vf-login-copy h1 { color:#fff; font-size:28px; line-height:1.08; font-weight:850; letter-spacing:-.04em; }
        .vf-login-copy p { margin-top:8px; color:#a3a3a3; font-size:14px; line-height:1.5; }
        .vf-login-card [data-flux-control] { min-height:48px; border-radius:12px !important; }
        .vf-login-card button[type="submit"] {
            min-height:50px; border-radius:12px; font-weight:800; background:#e63946 !important;
        }
        .vf-login-meta { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .vf-login-register {
            margin-top:4px; padding-top:20px; border-top:1px solid rgba(255,255,255,.07);
            text-align:center; color:#737373; font-size:13px;
        }
        @media (max-width: 420px) {
            .vf-login-card { padding:22px 18px; border-radius:20px; }
            .vf-login-copy h1 { font-size:25px; }
            .vf-login-meta { align-items:flex-start; flex-direction:column; }
        }
    </style>

    <div class="vf-login-card">
        <div class="vf-login-brand">
            <div class="vf-login-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="white" stroke-width="2.1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 9v6m14-6v6M3 10.5h2m14 0h2M7 7.5v9m10-9v9M7 12h10"/>
                </svg>
            </div>
            <div>
                <strong>VisionFit</strong>
                <span>Entrená con propósito</span>
            </div>
        </div>

        <div class="vf-login-copy">
            <h1>Bienvenido de nuevo</h1>
            <p>Ingresá para ver tu rutina, registrar tus series y seguir tu progreso.</p>
        </div>

        <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="tu@email.com"
            />

            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Tu contraseña')"
                viewable
            />

            <div class="vf-login-meta">
                <flux:checkbox name="remember" :label="__('Recordarme')" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link class="text-sm" :href="route('password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </flux:link>
                @endif
            </div>

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Ingresar') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <div class="vf-login-register">
                <span>{{ __('¿Todavía no tenés cuenta?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Crear cuenta') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
