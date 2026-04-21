<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kolae') }}</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Estilos específicos para o rodapé dos inputs do login (Lembrar-me / Esqueceu a senha) */
        .auth-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: var(--cyan-soft);
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .forgot-link:hover {
            opacity: 0.8;
            border-bottom: 1px solid var(--cyan-soft);
        }
    </style>
</head>

<body class="page-auth">

    <div class="bg-mesh" aria-hidden="true"></div>

    <div class="mobile-bg" aria-hidden="true">
        <img src="{{ asset('assets/img/login_bg.webp') }}" alt="">
    </div>

    <div class="page-wrap">

        {{-- ── LEFT PANEL ── --}}
        <div class="left-panel" aria-hidden="true">
            <div class="left-bg" style="background-image: url('{{ asset("assets/img/login_bg.webp") }}')"></div>
            <div class="left-overlay"></div>

            <div class="deco-rings">
                <svg viewBox="0 0 380 380" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="190" cy="190" r="170" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="8 18" />
                    <circle cx="190" cy="190" r="125" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="5 12" />
                    <circle cx="190" cy="190" r="75" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="3 8" />
                </svg>
            </div>

            <div class="left-content">
                <div class="left-logo">
                    <img src="{{ asset('assets/img/kolae_branca.png') }}" alt="{{ config('app.name') }}">
                </div>

                <div class="left-headline">
                    <h1>{{ __('Bem-vindo de volta ao') }}<br><span>{{ config('app.name') }}</span></h1>
                    <p>{{ __('Acesse sua conta para continuar organizando seus esportes com facilidade.') }}</p>
                </div>

                {{-- Status simplificado para a tela de login --}}
                <div class="stats-row">
                    <div>
                        <span class="stat-num">100%</span>
                        <span class="stat-lbl">{{ __('Foco no Jogo') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT PANEL ── --}}
        <div class="right-panel">
            <div class="card">

                <a href="{{ route('home') }}" class="btn-back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 5l-7 7 7 7" />
                    </svg>
                    {{ __('Voltar') }}
                </a>

                <a href="{{ route('home') }}" class="mobile-logo">
                    <img src="{{ asset('assets/img/kolae_branca.png') }}" alt="{{ config('app.name') }}">
                </a>

                <h1 class="card-title">{{ __('Entrar na sua') }} <span>{{ __('conta') }}</span></h1>
                <p class="card-sub">{{ __('Preencha seus dados para acessar.') }}</p>

                {{-- Erros de Validação do Breeze --}}
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-circle-exclamation alert-icon"></i>
                        <div>
                            @if ($errors->count() === 1)
                                {{ $errors->first() }}
                            @else
                                {{ __('Oops! Encontramos alguns erros:') }}
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Status de Sessão (Ex: "Senha redefinida com sucesso") --}}
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-circle-check alert-icon"></i>
                        {{ session('status') }}
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- E-mail --}}
                    <div class="fg">
                        <label class="form-label" for="email">{{ __('E-mail') }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required autofocus
                                value="{{ old('email') }}"
                                class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                placeholder="{{ __('seu@email.com') }}">
                        </div>
                    </div>

                    {{-- Senha --}}
                    <div class="fg" style="margin-bottom: 0.5rem;">
                        <label class="form-label" for="password">{{ __('Senha') }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="form-input has-eye {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                placeholder="••••••••">
                            
                            {{-- Botão de mostrar senha integrado com o JS do app.js --}}
                            <button type="button" class="btn-eye" data-toggle="password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Lembrar-me e Esqueceu a Senha --}}
                    <div class="auth-actions">
                        <label class="cb-label" style="margin-top: 0;">
                            <input id="remember_me" name="remember" type="checkbox">
                            <span class="cb-box">
                                <svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3" /></svg>
                            </span>
                            <span class="cb-text">{{ __('Lembrar de mim') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">
                                {{ __('Esqueceu a senha?') }}
                            </a>
                        @endif
                    </div>

                    {{-- Botão Entrar --}}
                    <button id="login-btn" type="submit" class="btn-submit">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="btn-text">{{ __('Entrar') }}</span>
                    </button>

                </form>

                <p class="form-footer">
                    {{ __('Ainda não tem conta?') }}
                    <a href="{{ route('register') }}">{{ __('Criar conta') }}</a>
                </p>

            </div>
        </div>
    </div>

    {{-- Precisamos desse objeto para o botão de mostrar senha do app.js funcionar aqui também --}}
    <script>
        window.KolaeAuthData = {
            translations: {
                showPassword: '{{ __("Mostrar senha") }}',
                hidePassword: '{{ __("Ocultar senha") }}'
            }
        };

        // Lógica simples do botão de carregar apenas para o login (já que o app.js bloqueia)
        const form = document.getElementById('login-form');
        const btn = document.getElementById('login-btn');
        if (form && btn) {
            form.addEventListener('submit', () => {
                btn.classList.add('loading');
                btn.disabled = true;
            });
        }
    </script>
</body>
</html>