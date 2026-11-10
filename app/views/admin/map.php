<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <?php $pageTitle = 'Mapa · Kolae'; include BASE_PATH . '/app/views/partials/head.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow: hidden; }

        /* Remove UI padrão do Google Maps */
        .gmnoprint a, .gmnoprint span, .gm-style-cc { display: none; }
        .gmnoprint div { background: none !important; }

        /* Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.5); border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: rgba(156, 163, 175, 0.8); }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">
    <?php $adminActive = 'mapa'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>





        <main class="relative w-full h-[calc(100dvh-4.5rem)]">

            <div id="map" class="w-full h-full z-0 outline-none bg-surface-base"></div>


            <div class="absolute top-4 left-16 right-4 md:left-1/2 md:-translate-x-1/2 md:w-96 z-10">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-content-secondary group-focus-within:text-brand-500 transition-colors"></i>
                    </div>
                    <input type="text" class="venue-search-input block w-full pl-10 pr-4 py-3 bg-surface-elevated/90 backdrop-blur-md border border-content-secondary/20 rounded-xl text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 shadow-xl transition-all" placeholder="Buscar local...">

                    <div class="venue-search-results absolute mt-2 w-full bg-surface-elevated border border-content-secondary/10 rounded-xl shadow-2xl z-20 hidden max-h-60 overflow-y-auto custom-scrollbar"></div>
                </div>
            </div>

            <div id="venue-sidebar" class="absolute top-0 right-0 h-full w-full sm:max-w-sm bg-surface-elevated/95 backdrop-blur-xl shadow-2xl p-6 transform transition-transform duration-300 ease-in-out translate-x-full z-30 border-l border-content-secondary/10 flex flex-col">
                <button id="close-sidebar-btn" class="absolute top-4 right-4 p-2 bg-surface-base rounded-full text-content-secondary hover:text-content-primary hover:bg-content-secondary/10 transition-all">
                    <i class="fas fa-times text-lg"></i>
                </button>
                
                <div class="mt-8 flex-1 overflow-y-auto custom-scrollbar">
                    <div class="relative aspect-video rounded-2xl overflow-hidden mb-6 shadow-lg border border-content-secondary/10">
                        <img id="venue-image" src="" alt="Foto do local" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                        <h2 id="venue-name" class="absolute bottom-4 left-4 right-4 text-xl font-bold text-white drop-shadow-md leading-tight"></h2>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0 text-brand-500">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <p class="text-xs text-content-secondary uppercase font-bold tracking-wider">Endereço</p>
                                <p id="venue-address" class="text-sm text-content-primary leading-relaxed"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-content-secondary/10 mt-auto">
                    <a id="venue-details-link" href="#" class="w-full flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-xl transition-all hover:shadow-lg hover:-translate-y-0.5">
                        Ver Detalhes Completos <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

        </main>

    <script>
        const venues = <?php echo $venuesJson ?? '[]'; ?>;
        
        // Estilo Dark (O seu original)
        const darkMapStyle = [
            { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] },
            { featureType: "poi", stylers: [{ visibility: "off" }] },
            { featureType: "transit", stylers: [{ visibility: "off" }] },
            { featureType: "road", elementType: "labels.icon", stylers: [{ visibility: "off" }] },
            { featureType: "road", elementType: "geometry", stylers: [{ color: "#38414e" }] },
            { featureType: "road", elementType: "geometry.stroke", stylers: [{ color: "#212a37" }] },
            { featureType: "road", elementType: "labels.text.fill", stylers: [{ color: "#9ca5b3" }] },
            { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#746855" }] },
            { featureType: "road.highway", elementType: "geometry.stroke", stylers: [{ color: "#1f2835" }] },
            { featureType: "road.highway", elementType: "labels.text.fill", stylers: [{ color: "#f3d19c" }] },
            { featureType: "water", elementType: "geometry", stylers: [{ color: "#17263c" }] },
            { featureType: "water", elementType: "labels.text.fill", stylers: [{ color: "#515c6d" }] },
            { featureType: "water", elementType: "labels.text.stroke", stylers: [{ color: "#17263c" }] }
        ];

        // Estilo Light (Limpo e minimalista)
        const lightMapStyle = [
            { featureType: "poi", stylers: [{ visibility: "off" }] },
            { featureType: "transit", stylers: [{ visibility: "off" }] },
            { featureType: "road", elementType: "labels.icon", stylers: [{ visibility: "off" }] },
            { featureType: "administrative", elementType: "geometry", stylers: [{ visibility: "off" }] },
            { featureType: "poi", elementType: "labels", stylers: [{ visibility: "off" }] }
        ];

        let map;

        function initMap() {
            // Detecta tema inicial
            const isDark = document.documentElement.classList.contains('dark') || localStorage.theme === 'dark';
            const initialStyle = isDark ? darkMapStyle : lightMapStyle;

            const mapCenter = { lat: -23.5505, lng: -46.6333 };

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: mapCenter,
                disableDefaultUI: true,
                zoomControl: false,
                styles: initialStyle
            });

            // Adiciona marcadores (Código igual ao seu)
            const venueSidebar = document.getElementById('venue-sidebar');
            const closeVenueBtn = document.getElementById('close-sidebar-btn');
            const bounds = new google.maps.LatLngBounds();
            const markers = [];

            closeVenueBtn.addEventListener('click', () => venueSidebar.classList.add('translate-x-full'));

            venues.forEach(venue => {
                const lat = parseFloat(venue.latitude);
                const lng = parseFloat(venue.longitude);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const position = { lat, lng };
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: venue.name,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: "#0084FF", // brand-500 (azul da marca)
                            fillOpacity: 1,
                            strokeWeight: 2,
                            strokeColor: "#FFFFFF" // Borda branca para contraste
                        }
                    });

                    marker.venueData = venue;
                    markers.push(marker);
                    bounds.extend(position);

                    marker.addListener('click', () => {
                        const imgPath = venue.image_path ?
                            `<?php echo BASE_URL; ?>/uploads/venues/${venue.id}/${venue.image_path}` :
                            '<?php echo BASE_URL; ?>/assets/img/venue_placeholder.svg';

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

            // Lógica de Busca (Mantida e adaptada cores)
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
                            item.className = 'p-3 hover:bg-surface-base cursor-pointer text-sm text-content-secondary hover:text-content-primary border-b border-content-secondary/10 last:border-0 transition-colors flex items-center gap-3';
                            item.innerHTML = `<div class="w-8 h-8 bg-surface-base rounded flex items-center justify-center text-brand-500"><i class="fas fa-map-marker-alt"></i></div><div><p class="font-medium"></p><p class="text-xs text-content-secondary truncate w-48"></p></div>`;
                            // Nome e cidade vêm do cadastro: entram como texto, nunca como HTML
                            const [nameEl, cityEl] = item.querySelectorAll('p');
                            nameEl.textContent = venue.name;
                            cityEl.textContent = venue.city;
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

        // Tema trocado no cabeçalho do admin: o mapa acompanha (D67)
        document.addEventListener('kolae:theme', (e) => {
            if (map) map.setOptions({ styles: e.detail.dark ? darkMapStyle : lightMapStyle });
        });

    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap"></script>
</body>
</html>