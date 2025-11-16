<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform -translate-x-full md:translate-x-0">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="p-6 text-center border-b border-gray-800">
                <div class="w-24 h-24 rounded-full bg-gray-700 mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-shield text-4xl text-cyan-400"></i>
                </div>
                <h2 class="text-xl font-bold">
                    <?php echo htmlspecialchars($userName ?? 'Admin'); ?>
                </h2>
                <p class="text-sm text-gray-400">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-home w-5 text-center"></i><span>Início</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-users w-5 text-center"></i><span>Usuários</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-running w-5 text-center"></i><span>Esportes</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg"><i class="fas fa-map-marker-alt w-5 text-center"></i><span>Mapa</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fa-solid fa-flag w-5 text-center"></i>
                    <span>Quadras</span>
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span></a>
            </div>
        </aside>

        <!-- Overlay para fechar o menu em mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

        <!-- Main Content -->
        <main class="md:ml-64 flex-1 relative">
            <div id="map" class="w-full h-full"></div>

            <!-- Mobile Top Bar: Menu + Search -->
            <div class="md:hidden absolute top-0 left-0 z-10 w-full p-4">
                <div class="flex items-center gap-3">
                    <button id="sidebar-toggle" class="flex-shrink-0 text-white bg-black/40 p-3 rounded-lg backdrop-blur-sm shadow-lg">
                        <i class="fas fa-bars text-lg"></i>
                    </button>

                    <!-- INÍCIO: BARRA DE PESQUISA (MOBILE) -->
                    <div class="relative flex-grow">
                        <div class="relative bg-[#161B22] border border-gray-700 rounded-lg shadow-lg">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-500"></i>
                            </div>
                            <input type="text" placeholder="Buscar local..." class="venue-search-input block w-full rounded-lg border-0 bg-transparent py-3 pl-10 pr-3 text-gray-200 placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                            <div class="venue-search-results absolute mt-1 w-full bg-[#161B22] border border-gray-700 rounded-lg shadow-lg z-20 hidden max-h-60 overflow-y-auto"></div>
                        </div>
                    </div>
                    <!-- FIM: BARRA DE PESQUISA (MOBILE) -->

                </div>
            </div>

            <!-- INÍCIO: BARRA DE PESQUISA (DESKTOP) -->
            <div class="hidden md:block absolute top-6 left-1/2 -translate-x-1/2 z-10 w-full max-w-md px-4">
                <div class="relative bg-[#161B22] border border-gray-700 rounded-lg shadow-lg">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-500"></i>
                    </div>
                    <input type="text" placeholder="Buscar pelo nome do local..." class="venue-search-input block w-full rounded-lg border-0 bg-transparent py-3 pl-10 pr-3 text-gray-200 placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm">
                    <div class="venue-search-results absolute mt-1 w-full bg-[#161B22] border border-gray-700 rounded-lg shadow-lg z-20 hidden max-h-60 overflow-y-auto"></div>
                </div>
            </div>
            <!-- FIM: BARRA DE PESQUISA (DESKTOP) -->

            <!-- Venue Info Sidebar -->
            <div id="venue-sidebar" class="absolute top-0 right-0 h-full w-full sm:max-w-sm bg-[#161B22] shadow-lg p-6 transform transition-transform duration-300 ease-in-out translate-x-full z-20 border-l border-gray-800">
                <button id="close-sidebar-btn" class="absolute top-4 right-4 text-gray-500 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div>
                    <img id="venue-image" src="" alt="Foto do local" class="w-full h-48 object-cover rounded-lg mb-4 bg-gray-800">
                    <h2 id="venue-name" class="text-2xl font-bold text-white"></h2>
                    <p id="venue-address" class="text-gray-400 mt-2"></p>
                    <a id="venue-details-link" href="#" class="mt-6 inline-block w-full text-center bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-4 rounded-lg transition-colors">
                        Ver Mais Detalhes
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        const venues = <?php echo $venuesJson ?? '[]'; ?>;

        function initMap() {
            const mapCenter = {
                lat: -23.5213,
                lng: -46.1884
            };
            const mapStyle = [{
                "elementType": "geometry",
                "stylers": [{
                    "color": "#242f3e"
                }]
            }, {
                "elementType": "labels.text.stroke",
                "stylers": [{
                    "color": "#242f3e"
                }]
            }, {
                "elementType": "labels.text.fill",
                "stylers": [{
                    "color": "#746855"
                }]
            }, {
                "featureType": "administrative.locality",
                "elementType": "labels.text.fill",
                "stylers": [{
                    "color": "#d59563"
                }]
            }, {
                "featureType": "poi",
                "stylers": [{
                    "visibility": "off"
                }]
            }, {
                "featureType": "road",
                "elementType": "geometry",
                "stylers": [{
                    "color": "#38414e"
                }]
            }, {
                "featureType": "road",
                "elementType": "geometry.stroke",
                "stylers": [{
                    "color": "#212a37"
                }]
            }, {
                "featureType": "road",
                "elementType": "labels.icon",
                "stylers": [{
                    "visibility": "off"
                }]
            }, {
                "featureType": "road.highway",
                "elementType": "geometry",
                "stylers": [{
                    "color": "#746855"
                }]
            }, {
                "featureType": "road.highway",
                "elementType": "geometry.stroke",
                "stylers": [{
                    "color": "#1f2835"
                }]
            }, {
                "featureType": "transit",
                "stylers": [{
                    "visibility": "off"
                }]
            }, {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [{
                    "color": "#17263c"
                }]
            }];

            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: mapCenter,
                disableDefaultUI: true,
                zoomControl: true,
                styles: mapStyle
            });

            const venueSidebar = document.getElementById('venue-sidebar');
            const closeBtn = document.getElementById('close-sidebar-btn');
            closeBtn.addEventListener('click', () => venueSidebar.classList.add('translate-x-full'));

            const bounds = new google.maps.LatLngBounds();
            const markers = [];

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
                            fillColor: "#38BDF8",
                            fillOpacity: 1,
                            strokeWeight: 2,
                            strokeColor: "#0D1117"
                        }
                    });

                    marker.venueData = venue;
                    markers.push(marker);
                    bounds.extend(position);

                    marker.addListener('click', () => {
                        document.getElementById('venue-image').src = venue.image_path ? `<?php echo BASE_URL; ?>/uploads/venues/${venue.id}/${venue.image_path}` : 'https://placehold.co/400x200/161B22/E0E0E0?text=Sem+Imagem';
                        document.getElementById('venue-name').textContent = venue.name;
                        document.getElementById('venue-address').textContent = `${venue.street}, ${venue.number} - ${venue.city}`;
                        document.getElementById('venue-details-link').href = `<?php echo BASE_URL; ?>/admin/quadras/editar/${venue.id}`; // Ajuste o link se necessário
                        venueSidebar.classList.remove('translate-x-full');
                    });
                }
            });

            if (venues.length > 0 && !bounds.isEmpty()) {
                map.fitBounds(bounds);
            }

            // --- INÍCIO: LÓGICA DA BARRA DE PESQUISA ---
            const searchInputs = document.querySelectorAll('.venue-search-input');
            searchInputs.forEach(input => {
                const resultsContainer = input.nextElementSibling;
                input.addEventListener('input', () => {
                    const searchTerm = input.value.toLowerCase().trim();
                    resultsContainer.innerHTML = '';
                    if (searchTerm.length === 0) {
                        resultsContainer.classList.add('hidden');
                        return;
                    }
                    const filteredVenues = venues.filter(v => v.name.toLowerCase().includes(searchTerm));
                    if (filteredVenues.length > 0) {
                        resultsContainer.classList.remove('hidden');
                        filteredVenues.forEach(venue => {
                            const resultItem = document.createElement('div');
                            resultItem.className = 'p-3 hover:bg-gray-700/50 cursor-pointer text-sm';
                            resultItem.textContent = venue.name;
                            resultItem.addEventListener('click', () => {
                                const targetMarker = markers.find(m => m.venueData.id === venue.id);
                                if (targetMarker) {
                                    map.setCenter(targetMarker.getPosition());
                                    map.setZoom(16);
                                    google.maps.event.trigger(targetMarker, 'click');
                                }
                                input.value = venue.name;
                                resultsContainer.classList.add('hidden');
                            });
                            resultsContainer.appendChild(resultItem);
                        });
                    } else {
                        resultsContainer.classList.add('hidden');
                    }
                });
            });

            document.addEventListener('click', (event) => {
                searchInputs.forEach(input => {
                    const resultsContainer = input.nextElementSibling;
                    if (!input.contains(event.target) && !resultsContainer.contains(event.target)) {
                        resultsContainer.classList.add('hidden');
                    }
                });
            });
            // --- FIM: LÓGICA DA BARRA DE PESQUISA ---
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mainSidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                if (mainSidebar) mainSidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                if (mainSidebar) mainSidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);
        });
    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap"></script>
</body>

</html>