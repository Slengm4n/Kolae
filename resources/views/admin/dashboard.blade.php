<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Painel Administrativo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-900 dark:text-gray-100 text-lg">Total de Usuários</h3>
                    <p class="text-3xl font-bold text-blue-500">{{ $totalUsers }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-900 dark:text-gray-100 text-lg">Quadras Cadastradas</h3>
                    <p class="text-3xl font-bold text-green-500">{{ $totalVenues }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>