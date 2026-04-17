<header class="absolute top-0 left-0 w-full z-30 py-6">
    <div class="container mx-auto px-4 flex justify-between items-center">

        <img src="{{ asset('assets/img/kolae_branca.png') }}"
             alt="Logo Kolae" {{ config('app.name') }}"
             class="h-10 drop-shadow-md filter dark:filter-none invert dark:invert-0">

        <nav class="hidden md:block">
            <ul class="flex items-center space-x-10">
                <li>
                    <a href="#sobre-nos"
                       class="font-semibold text-white hover:text-cyan-400 transition-colors drop-shadow-md">
                        {{ __('messages.global_menu_about') }}
                    </a>
                </li>

                @auth
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="font-bold text-cyan-400 hover:text-cyan-300 transition-colors border border-cyan-400 px-4 py-2 rounded-full backdrop-blur-sm">
                        {{ __('messages.global_home_panel') }}
                    </a>
                </li>
                @endauth
            </ul>
        </nav>

        <div class="flex items-center gap-4">

            {{-- Seletor de idioma --}}
            <div class="relative z-50" id="lang-wrapper">
    <button id="lang-btn"
        class="flex items-center gap-2 px-3 py-2 rounded-full bg-surface-elevated/50 backdrop-blur-sm border border-gray-700/50 hover:bg-surface-elevated transition-all text-white text-sm">
        <i class="fas fa-globe text-base"></i>
        <span class="hidden sm:inline">{{ strtoupper(session('idioma', 'pt-br')) }}</span>
        <i class="fas fa-chevron-down text-xs opacity-60 transition-transform duration-200" id="lang-chevron"></i>
    </button>

    <div id="lang-dropdown"
        class="absolute top-full right-0 mt-2 w-48 bg-[#1e293b] border border-gray-700/50 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50 overflow-hidden">
        @foreach([
            ['lang' => 'pt-br', 'flag' => '🇧🇷', 'label' => 'Português'],
            ['lang' => 'en-us', 'flag' => '🇺🇸', 'label' => 'English'],
            ['lang' => 'es-es', 'flag' => '🇪🇸', 'label' => 'Español'],
            ['lang' => 'hi-in', 'flag' => '🇮🇳', 'label' => 'हिन्दी'],
            ['lang' => 'zh-cn', 'flag' => '🇨🇳', 'label' => '中文'],
            ['lang' => 'it-it', 'flag' => '🇮🇹', 'label' => 'Italiano'],
            ['lang' => 'ja-jp', 'flag' => '🇯🇵', 'label' => '日本語'],
        ] as $item)
        <a href="{{ route('lang.switch', $item['lang']) }}"
           class="flex items-center gap-3 px-4 py-2.5 text-sm text-white hover:bg-cyan-500/20 hover:text-cyan-400 transition-colors
                  {{ session('idioma', 'pt-br') === $item['lang'] ? 'bg-cyan-500/10 text-cyan-400' : '' }}">
            <span class="text-lg">{{ $item['flag'] }}</span>
            {{ $item['label'] }}
            @if(session('idioma', 'pt-br') === $item['lang'])
                <i class="fas fa-check text-xs ml-auto text-cyan-400"></i>
            @endif
        </a>
        @endforeach
    </div>
</div>

            {{-- Menu do usuário --}}
            <div class="relative">
                <div id="user-menu-button"
                    class="flex items-center gap-4 p-2 border border-gray-700/50 rounded-full cursor-pointer transition-colors hover:bg-surface-secondary bg-surface-elevated/80 backdrop-blur-sm text-content-primary">
                    <i class="fas fa-bars text-lg ml-2"></i>

                    @auth
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('uploads/avatars/' . Auth::id() . '/' . Auth::user()->avatar) }}"
                                 class="w-8 h-8 rounded-full object-cover border border-gray-600"
                                 alt="Avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                    @else
                        <i class="fas fa-user-circle text-3xl text-content-secondary"></i>
                    @endauth
                </div>

                <div id="profile-dropdown"
                    class="absolute top-full right-0 mt-4 w-72 bg-surface-elevated border border-gray-700/50 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-300 z-50">
                    <ul class="py-2 text-content-primary">

                        <li class="md:hidden">
                            <a href="#sobre-nos"
                               class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-surface-secondary transition-colors">
                                <i class="fas fa-info-circle w-5 text-center text-content-secondary"></i>
                                {{ __('messages.global_menu_about') }}
                            </a>
                        </li>
                        <li class="border-t border-gray-700/50 my-2 md:hidden"></li>

                        @auth
                            <li>
                                <div class="px-5 py-2 text-xs text-content-secondary uppercase font-bold">
                                    {{ __('messages.global_account') }}
                                </div>
                            </li>
                            <li>
                                <a href="{{ route('dashboard') }}"
                                   class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-surface-secondary transition-colors">
                                    <i class="fas fa-columns w-5 text-center text-cyan-400"></i>
                                    {{ __('messages.global_home_panel') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-surface-secondary transition-colors">
                                    <i class="fas fa-user-cog w-5 text-center text-content-secondary"></i>
                                    {{ __('messages.global_menu_profile') }}
                                </a>
                            </li>
                            <li class="border-t border-gray-700/50 my-2"></li>
                            <li>
                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                   class="flex items-center gap-4 px-5 py-3 text-sm text-red-400 hover:bg-surface-secondary transition-colors">
                                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                                    {{ __(key: 'messages.global_menu_exit') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}"
                                   class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-surface-secondary transition-colors">
                                    <i class="fas fa-sign-in-alt w-5 text-center text-content-secondary"></i>
                                    {{ __('messages.global_menu_login') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('register') }}"
                                   class="flex items-center gap-4 px-5 py-3 text-sm font-bold text-cyan-400 hover:bg-surface-secondary transition-colors">
                                    <i class="fas fa-user-plus w-5 text-center"></i>
                                    {{ __('messages.global_menu_register') }}
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>

        </div>
    </div>
</header>
