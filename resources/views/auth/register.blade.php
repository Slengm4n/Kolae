<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kolae')}}</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="page-register">

    <div class="bg-mesh" aria-hidden="true"></div>

    <div class="mobile-bg" aria-hidden="true">
        <img src="{{ asset('images/register_bg.webp') }}" alt="">
    </div>

    <div class="page-wrap">

        {{-- ── LEFT PANEL ── --}}
        <div class="left-panel" aria-hidden="true">
            <div class="left-bg" style="background-image: url('{{ asset("assets/img/register_bg.webp") }}')"></div>
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
                    <h1>{{ __('Organize sua vida com') }}<br><span>{{ __('clareza') }}</span></h1>
                    <p>{{ __('Tudo que você precisa em um só lugar. Rápido, bonito e feito para você.') }}</p>
                </div>

                <div class="stats-row">
                    <div>
                        <span class="stat-num">10k+</span>
                        <span class="stat-lbl">{{ __('Usuários') }}</span>
                    </div>
                    <div>
                        <span class="stat-num">4.9★</span>
                        <span class="stat-lbl">{{ __('Avaliação') }}</span>
                    </div>
                    <div>
                        <span class="stat-num">100%</span>
                        <span class="stat-lbl">{{ __('Gratuito') }}</span>
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
                    <img src="{{ asset('images/kolae_branca.png') }}" alt="{{ config('app.name') }}">
                </a>

                <h1 class="card-title">{{ __('Criar sua') }} <span>{{ __('conta') }}</span></h1>
                <p class="card-sub">{{ __('Preencha os campos abaixo para começar.') }}</p>

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-circle-exclamation alert-icon"></i>
                        <div>
                            @if ($errors->count() === 1)
                                {{ $errors->first() }}
                            @else
                                {{ __('Por favor, corrija os erros abaixo:') }}
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-circle-check"></i>
                        {{ session('status') }}
                    </div>
                @endif

                <form id="register-form" method="POST" action="{{ route('register') }}">
                    @csrf

                    {{-- Nome --}}
                    <div class="fg">
                        <label class="form-label" for="name">{{ __('Nome completo') }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-user input-icon" aria-hidden="true"></i>
                            <input id="name" name="name" type="text" autocomplete="name" required autofocus
                                value="{{ old('name') }}"
                                class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                placeholder="{{ __('Seu nome completo') }}">
                        </div>
                        @error('name')
                            <p class="field-error">
                                <i class="fas fa-triangle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- E-mail --}}
                    <div class="fg">
                        <label class="form-label" for="email">{{ __('E-mail') }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                value="{{ old('email') }}"
                                class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                placeholder="{{ __('seu@email.com') }}">
                        </div>
                        @error('email')
                            <p class="field-error">
                                <i class="fas fa-triangle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Data de Nascimento (agora ocupando a linha inteira) --}}
                    <div class="fg">
                        <label class="form-label" for="birthdate">{{ __('Data de nascimento') }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-calendar-days input-icon" aria-hidden="true"></i>
                            <input id="birthdate" name="birthdate" type="date" required value="{{ old('birthdate') }}"
                                class="form-input {{ $errors->has('birthdate') ? 'is-invalid' : '' }}"
                                max="{{ now()->subYears(13)->format('Y-m-d') }}">
                        </div>
                        @error('birthdate')
                            <p class="field-error">
                                <i class="fas fa-triangle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Senhas --}}
                    <div class="form-row-2">
                        <div>
                            <label class="form-label" for="password">{{ __('Senha') }}</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                                <input id="password" name="password" type="password" autocomplete="new-password" required
                                    class="form-input has-eye {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                    placeholder="••••••••">
                                <button type="button" class="btn-eye" data-toggle="password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="pw-strength" id="pw-strength" aria-live="polite">
                                <div class="pw-bars">
                                    <div class="pw-bar" id="pw-bar-1"></div>
                                    <div class="pw-bar" id="pw-bar-2"></div>
                                    <div class="pw-bar" id="pw-bar-3"></div>
                                </div>
                                <span class="pw-label" id="pw-label"></span>
                            </div>
                            @error('password')
                                <p class="field-error">
                                    <i class="fas fa-triangle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="password_confirmation">{{ __('Confirmar') }}</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                                <input id="password_confirmation" name="password_confirmation" type="password"
                                    autocomplete="new-password" required class="form-input has-eye"
                                    placeholder="••••••••">
                                <button type="button" class="btn-eye" data-toggle="password_confirmation" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Termos --}}
                    <div class="fg">
                        <label class="cb-label">
                            <input id="terms" name="terms" type="checkbox" required {{ old('terms') ? 'checked' : '' }}>
                            <span class="cb-box">
                                <svg viewBox="0 0 12 12">
                                    <polyline points="2,6 5,9 10,3" />
                                </svg>
                            </span>
                            <span class="cb-text">
                                {{ __('Concordo com os') }}
                                <a href="#" target="_blank">{{ __('Termos de uso') }}</a>
                                {{ __('e') }}
                                <a href="#" target="_blank">{{ __('Política de privacidade') }}</a>.
                            </span>
                        </label>
                        @error('terms')
                            <p class="field-error">
                                <i class="fas fa-triangle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button id="register-btn" type="submit" class="btn-submit">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="btn-text">{{ __('Criar conta') }}</span>
                    </button>

                </form>

                <p class="form-footer">
                    {{ __('Já tem uma conta?') }}
                    <a href="{{ route('login') }}">{{ __('Entrar') }}</a>
                </p>

            </div>
        </div>
    </div>
<script>
        window.KolaeRegisterData = {
            translations: {
                weak: '{{ __("Fraca") }}',
                medium: '{{ __("Média") }}',
                strong: '{{ __("Forte") }}'
            }
        };
    </script>
</body>
</html>