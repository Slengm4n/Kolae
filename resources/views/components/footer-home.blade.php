<footer class="bg-surface-elevated pt-16 md:pt-20 border-t border-gray-700/30 transition-colors duration-500">
    <div class="container mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-12">

        <div class="mb-8 text-center md:text-left">
            <img src="{{ asset('assets/img/kolae_branca.png') }}"
                 alt="Logo Kolae"
                 class="h-10 mx-auto md:mx-0 filter dark:filter-none invert dark:invert-0"
                 loading="lazy">
            <p class="text-sm text-content-secondary mt-4">{{ __('messages.home_footer_activity') }}</p>
            <div class="flex space-x-4 mt-6 justify-center md:justify-start text-content-primary">
                <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>

        <div class="mb-8 text-center md:text-left">
            <h3 class="text-lg font-semibold mb-4 text-content-primary">{{ __('messages.global_contact_kolae') }}</h3>
            <p class="text-sm text-content-secondary">
                <a href="mailto:kolae.gg@gmail.com" class="hover:text-cyan-400 transition-colors">kolae.gg@gmail.com</a>
            </p>
            <p class="text-sm text-content-secondary mt-2">+55 (11) 99860-0253</p>
        </div>

        <div class="mb-8 text-center md:text-left">
            <h3 class="text-lg font-semibold mb-4 text-content-primary">{{ __('messages.home_footer_register') }}</h3>
            <p class="text-sm text-content-secondary">
                Cadastre-se para ficar por dentro dos próximos eventos e atualizações.
            </p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex mt-4">
                @csrf
                <input type="email"
                       name="email"
                       placeholder="{{ __('messages.home_footer_email_placeholder') }}"
                       required
                       class="w-full bg-surface-base text-content-primary border border-gray-700 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400">
                <button type="submit"
                        aria-label="Enviar email"
                        class="bg-cyan-400 text-black font-bold px-4 py-2 rounded-r-md hover:bg-cyan-300 transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>

    </div>

    <div class="mt-8 md:mt-12 py-6 border-t border-gray-700/30 text-center">
        <p class="text-sm text-content-secondary">&copy; {{ __('messages.global_Copyright_message') }}</p>
    </div>
</footer>