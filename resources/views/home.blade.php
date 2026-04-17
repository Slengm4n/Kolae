{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', config('app.name', 'Kolae'))

@section('content')

{{-- ─── Botão de tema ─── --}}
<button id="theme-toggle"
    class="fixed top-24 right-4 z-50 p-3 rounded-full bg-surface-elevated text-content-primary shadow-lg border border-gray-700/20 hover:scale-110 transition-transform cursor-pointer">
    <i class="fas fa-sun hidden dark:block text-yellow-400"></i>
    <i class="fas fa-moon block dark:hidden text-blue-600"></i>
</button>

{{-- ─── Header ─── --}}
<x-header-home />

{{-- ─── Main ─── --}}
<main>

    {{-- Hero --}}
    <section class="relative h-screen flex items-center justify-center text-center md:justify-start md:text-left p-0 overflow-hidden">

        <video autoplay muted loop playsinline class="vimeo-bg-cover">
            <source src="{{ asset('assets/video/hero-bg.mp4') }}" type="video/mp4">
        </video>

        <div class="absolute top-0 left-0 w-full h-full bg-black/60 z-[-1]"></div>

        <div class="container mx-auto px-4 relative z-10 text-white">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold max-w-lg leading-tight mx-auto md:mx-0">
                {{ __('messages.global_slogan_headline') }}
            </h1>
            <div class="flex flex-wrap gap-4 mt-8 justify-center md:justify-start">
                <a href="{{ route('login') }}"
                   class="py-3 px-8 rounded-full font-semibold transition-all duration-300 bg-white text-black border-2 border-white hover:bg-transparent hover:text-white">
                    {{ __('messages.home_start_free') }}
                </a>
            </div>
        </div>
    </section>

    {{-- Carousel --}}
    <section class="bg-surface-secondary py-16 md:py-24 overflow-hidden transition-colors duration-500">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold max-w-2xl mx-auto text-content-primary">
                {{ __('messages.home_connect_message') }}
            </h2>
            <p class="max-w-3xl mx-auto mt-4 text-content-secondary">
                {{ __('messages.home_search_message') }}
            </p>
        </div>

        <div class="container mx-auto px-4 mt-12 md:mt-16 pb-10">
            <div class="swiper intro-carousel h-[480px]">
                <div class="swiper-wrapper">
                    @foreach([
                        'https://images.pexels.com/photos/47730/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                        'https://images.pexels.com/photos/270085/pexels-photo-270085.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                        'https://images.pexels.com/photos/163452/basketball-dunk-blue-game-163452.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                        'https://images.pexels.com/photos/1263349/pexels-photo-1263349.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                        'https://images.pexels.com/photos/863988/pexels-photo-863988.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                        'https://images.pexels.com/photos/1080884/pexels-photo-1080884.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                    ] as $img)
                    <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                        <img src="{{ $img }}" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    {{-- Sobre nós --}}
    <section id="sobre-nos" class="py-16 md:py-24 bg-surface-base text-content-primary transition-colors duration-500">
        <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
            <div class="order-last lg:order-first">
                <img src="{{ asset('assets/img/about_us_img.png') }}"
                     alt="About"
                     class="w-full rounded-xl shadow-lg"
                     loading="lazy">
            </div>
            <div class="text-center lg:text-left">
                <h2 class="text-3xl md:text-4xl font-bold">{{ __('messages.global_title_about') }}</h2>
                <p class="mt-4 text-content-secondary">{{ __('messages.global_text_about') }}</p>

                <div class="mt-8 flex flex-col sm:flex-row gap-8 justify-center lg:justify-start">
                    <div>
                        <i class="fas fa-users text-cyan-400 text-3xl mb-3"></i>
                        <h3 class="text-lg font-bold">{{ __('messages.home_title_community') }}</h3>
                        <p class="text-sm text-content-secondary">{{ __('messages.home_text_community') }}</p>
                    </div>
                    <div>
                        <i class="fas fa-map-marker-alt text-cyan-400 text-3xl mb-3"></i>
                        <h3 class="text-lg font-bold">{{ __('messages.home_title_location') }}</h3>
                        <p class="text-sm text-content-secondary">{{ __('messages.home_text_location') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Apoiadores --}}
    <section class="bg-surface-secondary py-16 md:py-24 transition-colors duration-500">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-content-primary">{{ __('messages.home_title_supporters') }}</h2>
            <p class="max-w-3xl mx-auto mt-4 text-content-secondary">{{ __('messages.home_text_supporters') }}</p>
        </div>

        @php
        $logos = [
            ['src' => asset('assets/img/logo_fatec.png'),           'alt' => 'Logo Fatec'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png', 'alt' => 'Logo Adidas'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png', 'alt' => 'Logo Nike'],
            ['src' => asset('assets/img/logo_atletica_sagui.png'),  'alt' => 'Logo Atletica Sagui'],
            ['src' => asset('assets/img/logo_brtz.png'),            'alt' => 'Logo BRTZ'],
            ['src' => asset('assets/img/logo_leos_de_ferraz.png'),  'alt' => 'Logo Leos de Ferraz'],
        ];
        @endphp

        <div class="w-full overflow-hidden relative mt-16 [mask-image:linear-gradient(to_right,transparent,black_20%,black_80%,transparent)]">
            <div class="flex animate-scroll hover:[animation-play-state:paused]">
                {{-- Duplicado para o loop infinito funcionar --}}
                @foreach(array_merge($logos, $logos) as $logo)
                <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                    <img src="{{ $logo['src'] }}"
                         alt="{{ $logo['alt'] }}"
                         class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"
                         loading="lazy">
                </div>
                @endforeach
            </div>
        </div>
    </section>

</main>

{{-- ─── Footer ─── --}}
<x-footer-home />
@endsection
