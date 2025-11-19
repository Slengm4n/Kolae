<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        /* Remove a UI padrão do Google Maps para limpar a tela */
        .gmnoprint a,
        .gmnoprint span,
        .gm-style-cc {
            display: none;
        }

        .gmnoprint div {
            background: none !important;
        }

        /* Scrollbar bonita para resultados */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #374151;
            border-radius: 20px;
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div class="flex h-screen w-full overflow-hidden">

        <aside id="sidebar" class="fixed top-0 left-0 z-50 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white transition-colors"><i class="fas fa-times text-2xl"></i></button>
            <div class="p-8 text-center border-b border-gray-800/50">
                <div class="w-20 h-20 rounded-full bg-gray-800 border border-gray-700 mx-auto flex items-center justify-center mb-4 shadow-inner"><i class="fas fa-user-shield text-3xl text-cyan-400"></i></div>
                <h2 class="text-lg font-bold text-white tracking-wide"><?php echo htmlspecialchars($userName ?? 'Admin'); ?></h2>
                <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-home w-5 text-center group-hover:text-cyan-400"></i><span>Início</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-users w-5 text-center group-hover:text-cyan-400"></i><span>Usuários</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-running w-5 text-center group-hover:text-purple-400"></i><span>Esportes</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 border-l-4 border-cyan-400 rounded-r-lg transition-all shadow-md"><i class="fas fa-map-marker-alt w-5 text-center"></i><span>Mapa</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-400"></i><span>Quadras</span></a>
            </nav>
            <div class="p-4 border-t border-gray-800/50">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span></a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/80 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 relative h-full w-full">

            <div id="map" class="w-full h-full z-0 outline-none"></div>

            <button id="sidebar-toggle" class="md:hidden absolute top-4 left-4 z-10 p-3 bg-[#161B22]/90 backdrop-blur-md rounded-xl text-cyan-400 border border-gray-700 shadow-lg active:scale-95 transition-transform">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <div class="absolute top-4 left-16 right-4 md:left-1/2 md:-translate-x-1/2 md:w-96 z-10">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 group-focus-within:text-cyan-400 transition-colors"></i>
                    </div>
                    <input type="text" class="venue-search-input block w-full pl-10 pr-4 py-3 bg-[#161B22]/90 backdrop-blur-md border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-xl transition-all" placeholder="Buscar local...">

                    <div class="venue-search-results absolute mt-2 w-full bg-[#161B22] border border-gray-700 rounded-xl shadow-2xl z-20 hidden max-h-60 overflow-y-auto custom-scrollbar"></div>
                </div>
            </div>

            <div id="venue-sidebar" class="absolute top-0 right-0 h-full w-full sm:max-w-sm bg-[#161B22]/95 backdrop-blur-xl shadow-2xl p-6 transform transition-transform duration-300 ease-in-out translate-x-full z-30 border-l border-gray-800 flex flex-col">
                <button id="close-sidebar-btn" class="absolute top-4 right-4 p-2 bg-gray-800/50 rounded-full text-gray-400 hover:text-white hover:bg-gray-700 transition-all">
                    <i class="fas fa-times text-lg"></i>
                </button>
                <div class="mt-8 flex-1 overflow-y-auto custom-scrollbar">
                    <div class="relative aspect-video rounded-2xl overflow-hidden mb-6 shadow-lg border border-gray-700">
                        <img id="venue-image" src="" alt="Foto do local" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                        <h2 id="venue-name" class="absolute bottom-4 left-4 right-4 text-xl font-bold text-white drop-shadow-md leading-tight"></h2>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center flex-shrink-0 text-cyan-400"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Endereço</p>
                                <p id="venue-address" class="text-sm text-gray-300 leading-relaxed"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-800 mt-auto">
                    <a id="venue-details-link" href="#" class="w-full flex items-center justify-center bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-6 rounded-xl transition-all hover:shadow-lg hover:-translate-y-0.5">
                        Ver Detalhes Completos <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script>
        const venues = <?php echo $venuesJson ?? '[]'; ?>;

        function initMap() {
            // === ESTILO SUPER LIMPO (DARK MODE) ===
            const mapStyle = [{
                    elementType: "geometry",
                    stylers: [{
                        color: "#242f3e"
                    }]
                },
                {
                    elementType: "labels.text.stroke",
                    stylers: [{
                        color: "#242f3e"
                    }]
                },
                {
                    elementType: "labels.text.fill",
                    stylers: [{
                        color: "#746855"
                    }]
                },

                // 1. Esconder TODOS os Pontos de Interesse (Lojas, Parques, Hospitais, etc.)
                {
                    featureType: "poi",
                    stylers: [{
                        visibility: "off"
                    }]
                },

                // 2. Esconder Transporte Público
                {
                    featureType: "transit",
                    stylers: [{
                        visibility: "off"
                    }]
                },

                // 3. Esconder ícones de Rodovias e Estradas (Mantém apenas o traçado)
                {
                    featureType: "road",
                    elementType: "labels.icon",
                    stylers: [{
                        visibility: "off"
                    }]
                },

                // 4. Esconder Ícones Administrativos (Bairros, etc - Mantém texto)
                {
                    featureType: "administrative",
                    elementType: "labels.icon",
                    stylers: [{
                        visibility: "off"
                    }]
                },

                // Cores das Estradas (para manter o contexto sem poluição)
                {
                    featureType: "road",
                    elementType: "geometry",
                    stylers: [{
                        color: "#38414e"
                    }]
                },
                {
                    featureType: "road",
                    elementType: "geometry.stroke",
                    stylers: [{
                        color: "#212a37"
                    }]
                },
                {
                    featureType: "road",
                    elementType: "labels.text.fill",
                    stylers: [{
                        color: "#9ca5b3"
                    }]
                },
                {
                    featureType: "road.highway",
                    elementType: "geometry",
                    stylers: [{
                        color: "#746855"
                    }]
                },
                {
                    featureType: "road.highway",
                    elementType: "geometry.stroke",
                    stylers: [{
                        color: "#1f2835"
                    }]
                },
                {
                    featureType: "road.highway",
                    elementType: "labels.text.fill",
                    stylers: [{
                        color: "#f3d19c"
                    }]
                },
                {
                    featureType: "water",
                    elementType: "geometry",
                    stylers: [{
                        color: "#17263c"
                    }]
                },
                {
                    featureType: "water",
                    elementType: "labels.text.fill",
                    stylers: [{
                        color: "#515c6d"
                    }]
                },
                {
                    featureType: "water",
                    elementType: "labels.text.stroke",
                    stylers: [{
                        color: "#17263c"
                    }]
                }
            ];

            const mapCenter = {
                lat: -23.5505,
                lng: -46.6333
            };

            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: mapCenter,
                disableDefaultUI: true, // Remove botões padrão do Google
                zoomControl: false,
                styles: mapStyle // Aplica o estilo limpo
            });

            const venueSidebar = document.getElementById('venue-sidebar');
            const closeVenueBtn = document.getElementById('close-sidebar-btn');
            const bounds = new google.maps.LatLngBounds();
            const markers = [];

            closeVenueBtn.addEventListener('click', () => venueSidebar.classList.add('translate-x-full'));

            venues.forEach(venue => {
                const lat = parseFloat(venue.latitude);
                const lng = parseFloat(venue.longitude);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const position = {
                        lat,
                        lng
                    };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: venue.name,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: "#38BDF8", // Azul Ciano Kolae
                            fillOpacity: 1,
                            strokeWeight: 2,
                            strokeColor: "#0D1117"
                        }
                    });

                    marker.venueData = venue;
                    markers.push(marker);
                    bounds.extend(position);

                    marker.addListener('click', () => {
                        const imgPath = venue.image_path ?
                            `<?php echo BASE_URL; ?>/uploads/venues/${venue.id}/${venue.image_path}` :
                            'https://placehold.co/600x400/161B22/E0E0E0?text=Sem+Imagem';

                        document.getElementById('venue-image').src = imgPath;
                        document.getElementById('venue-name').textContent = venue.name;
                        document.getElementById('venue-address').textContent = `${venue.street}, ${venue.number} - ${venue.city}`;
                        document.getElementById('venue-details-link').href = `<?php echo BASE_URL; ?>/admin/quadras/editar/${venue.id}`;

                        venueSidebar.classList.remove('translate-x-full');
                    });
                }
            });

            if (venues.length > 0 && !bounds.isEmpty()) {
                map.fitBounds(bounds);
            }

            // Lógica de Pesquisa Flutuante
            const searchInputs = document.querySelectorAll('.venue-search-input');
            searchInputs.forEach(input => {
                const resultsContainer = input.nextElementSibling;
                input.addEventListener('input', () => {
                    const term = input.value.toLowerCase().trim();
                    resultsContainer.innerHTML = '';
                    if (term.length === 0) {
                        resultsContainer.classList.add('hidden');
                        return;
                    }
                    const filtered = venues.filter(v => v.name.toLowerCase().includes(term));
                    if (filtered.length > 0) {
                        resultsContainer.classList.remove('hidden');
                        filtered.forEach(venue => {
                            const item = document.createElement('div');
                            item.className = 'p-3 hover:bg-gray-700/50 cursor-pointer text-sm text-gray-300 hover:text-white border-b border-gray-800 last:border-0 transition-colors flex items-center gap-3';
                            item.innerHTML = `<div class="w-8 h-8 bg-gray-800 rounded flex items-center justify-center text-cyan-400"><i class="fas fa-map-marker-alt"></i></div><div><p class="font-medium">${venue.name}</p><p class="text-xs text-gray-500 truncate w-48">${venue.city}</p></div>`;
                            item.addEventListener('click', () => {
                                const target = markers.find(m => m.venueData.id === venue.id);
                                if (target) {
                                    map.setCenter(target.getPosition());
                                    map.setZoom(16);
                                    google.maps.event.trigger(target, 'click');
                                }
                                input.value = '';
                                resultsContainer.classList.add('hidden');
                            });
                            resultsContainer.appendChild(item);
                        });
                    } else {
                        resultsContainer.classList.add('hidden');
                    }
                });
            });
            document.addEventListener('click', (e) => {
                searchInputs.forEach(input => {
                    const container = input.nextElementSibling;
                    if (!input.contains(e.target) && !container.contains(e.target)) container.classList.add('hidden');
                });
            });
        }

        // Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap"></script>
</body>

</html>