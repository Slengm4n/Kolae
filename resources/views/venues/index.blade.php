<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Minhas Quadras') }}
            </h2>
            <a href="{{ route('venues.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                + Nova Quadra
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Sucesso!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($venues as $venue)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-full hover:shadow-md transition-shadow">
                        
                        <div class="h-48 w-full bg-gray-200 dark:bg-gray-700 relative">
                            @if($venue->images->count() > 0)
                                <img src="{{ Storage::url($venue->images->first()->file_path) }}" alt="{{ $venue->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="flex items-center justify-center h-full text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                            
                            <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded 
                                {{ $venue->status === 'available' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                                {{ $venue->status === 'available' ? 'Disponível' : 'Indisponível' }}
                            </span>
                        </div>

                        <div class="p-6 flex-grow">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $venue->name }}</h3>
                            
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $venue->address->city ?? 'Sem Cidade' }} - {{ $venue->address->state ?? 'UF' }}
                            </h3>

                            <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400 mt-2">
                                <span>{{ ucfirst($venue->floor_type) }}</span>
                                <span class="font-bold text-lg text-blue-600">R$ {{ number_format($venue->average_price_per_hour, 2, ',', '.') }}/h</span>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-between items-center border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ route('venues.edit', $venue->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                Editar
                            </a>

                            <form action="{{ route('venues.destroy', $venue->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar esta quadra?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">Você ainda não tem nenhuma quadra cadastrada.</div>
                        <a href="{{ route('venues.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Cadastrar Minha Primeira Quadra
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>