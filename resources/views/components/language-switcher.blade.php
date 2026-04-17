//Componete da troca de idioma, utilizado no header e no modal de idioma
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