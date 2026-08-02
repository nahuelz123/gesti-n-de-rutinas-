<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <div style="text-align:center; margin-bottom:4px;">
            <div style="font-size:22px; font-weight:900;">VisionFit</div>
            <div style="font-size:14px; opacity:0.7; margin-top:4px;">Te estás uniendo a <b>{{ $gym->name }}</b></div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($errors->any())
            <div style="background:#3a1418; border:1px solid #7a1f28; color:#ffb3b8; border-radius:10px; padding:12px 14px; font-size:13px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @auth
            <div style="text-align:center; font-size:13px; opacity:0.8;">
                Ya iniciaste sesión como <b>{{ auth()->user()->name }}</b>.
                <div style="margin-top:10px; display:flex; gap:8px; justify-content:center;">
                    <flux:button href="/dashboard" variant="primary">Ir a mi cuenta</flux:button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button type="submit" variant="ghost">Cerrar sesión</flux:button>
                    </form>
                </div>
            </div>
        @else
            {{-- Selector: ya tengo cuenta / soy nuevo --}}
            <div style="display:flex; border-radius:10px; overflow:hidden; border:1px solid rgb(63 63 70);">
                <button type="button" id="tab-login" class="gj-tab gj-tab-active" style="flex:1; padding:10px; font-size:13px; font-weight:700; border:none; cursor:pointer;">
                    Ya tengo cuenta
                </button>
                <button type="button" id="tab-register" class="gj-tab" style="flex:1; padding:10px; font-size:13px; font-weight:700; border:none; cursor:pointer;">
                    Soy nuevo, registrarme
                </button>
            </div>

            {{-- Ya tengo cuenta --}}
            <div id="panel-login">
                <p style="font-size:13px; text-align:center; opacity:0.75; margin-bottom:12px;">
                    Iniciá sesión con tu cuenta de siempre. Si ya pertenecés a {{ $gym->name }}, vas a entrar directo a tu panel.
                </p>
                <flux:button href="{{ route('login') }}" variant="primary" class="w-full">Iniciar sesión</flux:button>
            </div>

            {{-- Soy nuevo --}}
            <div id="panel-register" style="display:none;">
                <form method="POST" action="{{ route('gym-join.register', $inviteCode) }}" class="flex flex-col gap-6">
                    @csrf

                    <flux:input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Tu nombre completo"
                    />

                    <flux:input
                        name="email"
                        label="Email"
                        :value="old('email')"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@ejemplo.com"
                    />

                    <flux:input
                        name="password"
                        label="Contraseña"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Contraseña"
                        viewable
                    />

                    <flux:input
                        name="password_confirmation"
                        label="Confirmar contraseña"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Repetí la contraseña"
                        viewable
                    />

                    <flux:button type="submit" variant="primary" class="w-full">
                        Crear mi cuenta en {{ $gym->name }}
                    </flux:button>
                </form>

                <p style="font-size:11px; text-align:center; opacity:0.5; margin-top:12px;">
                    ¿Sos coach? Registrate igual como alumno — tu profe/admin te cambia el rol después desde el panel.
                </p>
            </div>
        @endauth
    </div>

    <style>
        .gj-tab { background: #18181b; color: #a1a1aa; }
        .gj-tab-active { background: #e63946 !important; color: #fff !important; }
    </style>

    <script>
        (function () {
            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');
            const panelLogin = document.getElementById('panel-login');
            const panelRegister = document.getElementById('panel-register');
            if (!tabLogin || !tabRegister) return;

            function showLogin() {
                panelLogin.style.display = '';
                panelRegister.style.display = 'none';
                tabLogin.classList.add('gj-tab-active');
                tabRegister.classList.remove('gj-tab-active');
            }

            function showRegister() {
                panelLogin.style.display = 'none';
                panelRegister.style.display = '';
                tabRegister.classList.add('gj-tab-active');
                tabLogin.classList.remove('gj-tab-active');
            }

            tabLogin.addEventListener('click', showLogin);
            tabRegister.addEventListener('click', showRegister);

            @if ($errors->any())
                showRegister();
            @endif
        })();
    </script>
</x-layouts.auth>
