// Access Token Mapbox
mapboxgl.accessToken = 'pk.eyJ1IjoibWFoZW5kcmFsYWJzIiwiYSI6ImNtcmx6eG1pcTA0c2cyenM2eWk2dnR4ZGkifQ.CmCFE_l3hWEeBPMQfRNsYQ';

// Koordinat Eksak Kantor Kelurahan Pal Lima
const lokasi = [109.2885727, -0.036937];
const centerKamera = [109.2885727, -0.037450];

// Membuat peta
const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: centerKamera,
    zoom: 16.5,
    pitch: 45,
    bearing: -15,
    antialias: true
});

// Zoom Control
map.addControl(new mapboxgl.NavigationControl());

// State Traffic Layer
let isTrafficOn = true;
let prokjaMarkers = [];

// Helper HTML Escape
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Ambil Data Cuaca Real-time untuk Kelurahan Pal Lima
async function getCuacaPalLima() {
    try {
        const res = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-0.036937&longitude=109.2885727&current_weather=true&hourly=relative_humidity_2m');
        if (!res.ok) throw new Error('Gagal mengambil data cuaca');
        const data = await res.json();
        const cw = data.current_weather;

        let icon = '☀️';
        let desc = 'Cerah';
        const code = cw.weathercode;

        if (code === 0) { icon = '☀️'; desc = 'Cerah'; }
        else if ([1, 2, 3].includes(code)) { icon = '⛅'; desc = 'Cerah Berawan'; }
        else if ([45, 48].includes(code)) { icon = '🌫️'; desc = 'Berkabut'; }
        else if ([51, 53, 55, 61, 63, 65].includes(code)) { icon = '🌧️'; desc = 'Hujan Gerimis'; }
        else if ([80, 81, 82].includes(code)) { icon = '🌦️'; desc = 'Hujan Deras'; }
        else if ([95, 96, 99].includes(code)) { icon = '⛈️'; desc = 'Badai Petir'; }

        const temp = Math.round(cw.temperature);
        const wind = Math.round(cw.windspeed);

        return { temp, wind, icon, desc };
    } catch (e) {
        return { temp: 31, wind: 10, icon: '⛅', desc: 'Cerah Berawan' };
    }
}

// Mapbox Popup Custom Kantor Kelurahan
const popupKantor = new mapboxgl.Popup({
    offset: [0, -28],
    closeButton: true,
    closeOnClick: false,
    className: 'custom-map-popup'
});

function buildPopupHTML(weather) {
    const weatherSection = weather ? `
        <div class="weather-tab mt-1.5 p-2 rounded-3 text-white shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid rgba(255,255,255,0.15);">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 1.5rem; line-height: 1;">${weather.icon}</span>
                    <div>
                        <div class="fw-bold text-white" style="font-size: 1.1rem; line-height: 1.1;">${weather.temp}°C</div>
                        <small class="text-warning fw-semibold" style="font-size: 10px;">${weather.desc}</small>
                    </div>
                </div>
                <div class="text-end" style="font-size: 9.5px; color: #cbd5e1;">
                    <div><i class="bi bi-wind text-info me-1"></i>${weather.wind} km/h</div>
                    <div><i class="bi bi-geo-alt text-danger me-1"></i>Pal Lima</div>
                </div>
            </div>
        </div>
    ` : `
        <div class="mt-1.5 p-1.5 rounded-3 text-center text-muted small bg-light" style="font-size: 11px;">
            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memuat cuaca...
        </div>
    `;

    return `
        <div style="font-family: 'Outfit', sans-serif; padding: 2px; color: #0f172a; max-width: 225px;">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger rounded-circle p-1 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                    <i class="bi bi-building-fill text-white" style="font-size: 10px;"></i>
                </span>
                <div>
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 12.5px; line-height: 1.2;">Kantor Kelurahan Pal Lima</h6>
                    <small class="text-muted" style="font-size: 9px;">Pusat Layanan KKN 12</small>
                </div>
            </div>
            
            <p class="mb-1 text-muted" style="font-size: 10px; line-height: 1.25;">
                Kec. Pontianak Barat, Kota Pontianak, Kalimantan Barat
            </p>

            ${weatherSection}

            <!-- Tab Kondisi Lalu Lintas -->
            <div class="d-flex align-items-center justify-content-between mt-1.5 p-1.5 px-2 rounded-3 bg-light border border-secondary border-opacity-10" style="font-size: 10px;">
                <span class="text-dark fw-semibold d-flex align-items-center gap-1">
                    <i class="bi bi-car-front-fill text-success" style="font-size: 11px;"></i> Lalu Lintas Utama
                </span>
                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 fw-bold" style="font-size: 9.5px;">
                    🟢 Lancar
                </span>
            </div>

            <div class="mt-2 pt-1 border-top text-center">
                <a href="https://maps.app.goo.gl/ZPPdFPtyNP9BAeJz7" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 w-100 fw-semibold shadow-none" style="font-size: 11px; padding: 5px 12px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none;">
                    📍 Buka Google Maps
                </a>
            </div>
        </div>
    `;
}

// Marker Utama Kantor Kelurahan
popupKantor.setHTML(buildPopupHTML(null));
const markerKantor = new mapboxgl.Marker({ color: "#ef4444" })
    .setLngLat(lokasi)
    .setPopup(popupKantor)
    .addTo(map);

getCuacaPalLima().then(w => {
    popupKantor.setHTML(buildPopupHTML(w));
    const hdrWeather = document.getElementById('hdrWeatherText');
    if (hdrWeather) {
        hdrWeather.innerHTML = `${w.icon} <strong>${w.temp}°C</strong> — ${w.desc}`;
    }
});

// FUNGSI UTAMA: Load Dynamic Markers Titik Lokasi Program Kerja
window.loadLokasiProkja = async function(flyToTarget = null) {
    try {
        const res = await fetch('proses_lokasi_prokja.php?action=list');
        const json = await res.json();
        if (!json.success || !json.data) return;

        // Bersihkan marker proker lama
        prokjaMarkers.forEach(m => m.remove());
        prokjaMarkers = [];

        // Cek login dengan aman tanpa TDZ error
        let isUserLoggedIn = false;
        try {
            if (typeof window.IS_LOGGED_IN !== 'undefined') {
                isUserLoggedIn = !!window.IS_LOGGED_IN;
            } else if (typeof IS_LOGGED_IN !== 'undefined') {
                isUserLoggedIn = !!IS_LOGGED_IN;
            }
        } catch (err) {
            isUserLoggedIn = false;
        }

        json.data.forEach(item => {
            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const kat = item.kategori_lokasi || 'Program Kerja';
            let iconClass = 'bi-geo-alt-fill';
            let bgBg = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            let badgeBg = 'bg-danger';

            if (kat.includes('Posyandu') || kat.includes('Kesehatan')) {
                iconClass = 'bi-heart-pulse-fill';
                bgBg = 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)';
                badgeBg = 'bg-pink';
            } else if (kat.includes('Sekolah') || kat.includes('Pendidikan')) {
                iconClass = 'bi-book-fill';
                bgBg = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
                badgeBg = 'bg-primary';
            } else if (kat.includes('Kerja Bakti') || kat.includes('Lingkungan')) {
                iconClass = 'bi-tree-fill';
                bgBg = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                badgeBg = 'bg-success';
            } else if (kat.includes('UMKM') || kat.includes('Ekonomi')) {
                iconClass = 'bi-shop';
                bgBg = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                badgeBg = 'bg-warning';
            }

            // Element Kustom Marker Peta
            const el = document.createElement('div');
            el.className = 'marker-prokja-item pulse-marker';
            el.style.width = '34px';
            el.style.height = '34px';
            el.style.borderRadius = '50%';
            el.style.background = bgBg;
            el.style.border = '2.5px solid #ffffff';
            el.style.boxShadow = '0 6px 16px rgba(0,0,0,0.35)';
            el.style.display = 'flex';
            el.style.alignItems = 'center';
            el.style.justifyContent = 'center';
            el.style.color = '#ffffff';
            el.style.fontSize = '14px';
            el.style.cursor = 'pointer';
            el.style.transition = 'transform 0.2s ease';
            el.title = item.nama_lokasi;

            el.innerHTML = `<i class="bi ${iconClass}"></i>`;

            const encodedNama = encodeURIComponent(item.nama_lokasi || '');

            const popupHTML = `
                <div style="font-family: 'Outfit', sans-serif; padding: 2px; color: #0f172a; max-width: 230px;">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="badge ${badgeBg} rounded-pill px-2 py-0.5 text-white" style="font-size: 9px; font-weight: 600;">${escapeHtml(kat)}</span>
                        <small class="text-muted" style="font-size: 8.5px;"><i class="bi bi-clock me-1"></i>${escapeHtml(item.pengunggah_nama || 'Anggota KKN')}</small>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 12.5px; line-height: 1.25;">${escapeHtml(item.nama_lokasi)}</h6>
                    ${item.judul_prokja ? `
                        <div class="p-1.5 rounded bg-light border border-secondary border-opacity-10 mb-1.5" style="font-size: 10px;">
                            <span class="fw-semibold text-danger d-block text-truncate"><i class="bi bi-journal-bookmark me-1"></i>${escapeHtml(item.judul_prokja)}</span>
                        </div>
                    ` : ''}
                    ${item.keterangan ? `<p class="mb-2 text-secondary" style="font-size: 10px; line-height: 1.35;">${escapeHtml(item.keterangan)}</p>` : ''}
                    
                    <div class="d-flex gap-1.5 mt-2 pt-1 border-top">
                        <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="btn btn-sm btn-primary rounded-pill px-2 py-1 w-100 fw-semibold text-white d-flex align-items-center justify-content-center gap-1 shadow-sm" style="font-size: 10px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none;">
                            📍 Navigasi Google Maps
                        </a>
                        ${isUserLoggedIn ? `
                            <button type="button" onclick="konfirmasiHapusLokasiBtn(${item.id}, '${escapeHtml(item.nama_lokasi).replace(/'/g, "\\'")}')" class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px;" title="Hapus Titik Lokasi Ini">
                                <i class="bi bi-trash-fill" style="font-size: 10px;"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;

            const popup = new mapboxgl.Popup({ offset: [0, -16], closeButton: true, className: 'custom-map-popup' })
                .setHTML(popupHTML);

            const m = new mapboxgl.Marker({ element: el })
                .setLngLat([lng, lat])
                .setPopup(popup)
                .addTo(map);

            prokjaMarkers.push(m);
        });

        // Update Panel Daftar Titik Lokasi Peta di Bawah Peta
        const listContainer = document.getElementById('containerDaftarTitikProkja');
        if (listContainer) {
            if (json.data.length === 0) {
                listContainer.innerHTML = '<div class="text-white-50 small p-2" style="font-size: 11px;">Belum ada titik lokasi proker. Klik <b>"+ Tambah Titik Proker"</b> di atas untuk menambahkan.</div>';
            } else {
                listContainer.innerHTML = json.data.map(item => {
                    const lat = parseFloat(item.latitude);
                    const lng = parseFloat(item.longitude);
                    const escapedNama = escapeHtml(item.nama_lokasi).replace(/'/g, "\\'");

                    return `
                        <div class="badge bg-white bg-opacity-10 border border-white border-opacity-15 rounded-3 p-2 text-start flex-shrink-0 d-flex align-items-center justify-content-between gap-2 shadow-sm" style="min-width: 230px; max-width: 290px; font-weight: normal;">
                            <div style="cursor: pointer; overflow: hidden; flex: 1;" onclick="map.flyTo({ center: [${lng}, ${lat}], zoom: 17, pitch: 45 });">
                                <div class="fw-semibold text-white text-truncate" style="font-size: 11.5px;">📍 ${escapeHtml(item.nama_lokasi)}</div>
                                <div class="text-warning text-truncate" style="font-size: 9.5px;"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(item.kategori_lokasi || 'Program Kerja')}</div>
                                <div class="text-white-50" style="font-size: 9px;">Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}</div>
                            </div>
                            ${isUserLoggedIn ? `
                                <button type="button" class="btn btn-sm btn-danger rounded-circle p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px;" title="Hapus Titik Koordinat Ini" onclick="konfirmasiHapusLokasiBtn(${item.id}, '${escapedNama}')">
                                    <i class="bi bi-trash-fill" style="font-size: 10.5px;"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }).join('');
            }
        }

        // Terbang ke lokasi baru jika diberikan
        if (flyToTarget && typeof flyToTarget.lat === 'number' && typeof flyToTarget.lng === 'number') {
            map.flyTo({ center: [flyToTarget.lng, flyToTarget.lat], zoom: 17, pitch: 45 });
        }
    } catch (e) {
        console.warn('Gagal memuat titik proker:', e);
    }
};

// Panggil secepatnya saat script dimuat
window.loadLokasiProkja();

// Mode ambil titik dari peta
let isPickingMapPoint = false;

window.pilihTitikDariPeta = function() {
    isPickingMapPoint = true;
    const modalEl = document.getElementById('modalLokasiProkja');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
    const mapEl = document.getElementById('map');
    if (mapEl) {
        mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        mapEl.style.cursor = 'crosshair';
    }
    const infoBar = document.getElementById('mapHeaderBadge') || document.getElementById('mapWeatherHeaderBadge');
    if (infoBar) {
        infoBar.innerHTML = `<span class="badge bg-warning text-dark px-3 py-1.5 fs-6 fw-bold shadow-sm pulse-marker">👇 Klik sembarang titik pada peta untuk memilih lokasi proker</span>`;
    }
};

// Klik Peta untuk Ambil Koordinat saat mengisi form
map.on('click', (e) => {
    const lat = e.lngLat.lat.toFixed(6);
    const lng = e.lngLat.lng.toFixed(6);

    const latInput = document.getElementById('lokasi_lat');
    const lngInput = document.getElementById('lokasi_lng');
    const modalEl = document.getElementById('modalLokasiProkja');

    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;

    const infoText = document.getElementById('koordinatPickInfo');
    if (infoText) {
        infoText.innerHTML = `<span class="text-success fw-bold">✓ Koordinat terpilih dari peta: ${lat}, ${lng}</span>`;
    }

    if (isPickingMapPoint) {
        isPickingMapPoint = false;
        const mapEl = document.getElementById('map');
        if (mapEl) mapEl.style.cursor = '';

        if (modalEl) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
    }
});

// Helper function untuk buka popup peta Kantor Kelurahan
window.bukaPopupMap = function() {
    map.flyTo({ center: centerKamera, zoom: 16.5, pitch: 45 });
    if (!popupKantor.isOpen()) {
        popupKantor.addTo(map);
    }
};

// Helper function toggle layer Lalu Lintas
window.toggleTrafficMap = function() {
    isTrafficOn = !isTrafficOn;
    const visibility = isTrafficOn ? 'visible' : 'none';
    if (map.getLayer('traffic-flow')) {
        map.setLayoutProperty('traffic-flow', 'visibility', visibility);
    }
    const btnLabel = document.getElementById('lblTrafficState');
    const btn = document.getElementById('btnToggleTraffic');
    if (btnLabel && btn) {
        if (isTrafficOn) {
            btnLabel.textContent = 'Lalu Lintas: ON';
            btn.className = 'badge bg-success bg-opacity-20 text-white border border-success border-opacity-30 rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5 border-0';
        } else {
            btnLabel.textContent = 'Lalu Lintas: OFF';
            btn.className = 'badge bg-secondary bg-opacity-20 text-white-50 border border-white border-opacity-10 rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5 border-0';
        }
    }
};

// Tunggu style selesai dimuat
map.on('load', () => {
    if (map.setFog) {
        map.setFog({});
    }

    const layers = map.getStyle().layers;
    let labelLayerId;

    for (const layer of layers) {
        if (layer.type === "symbol" && layer.layout && layer.layout["text-field"]) {
            labelLayerId = layer.id;
            break;
        }
    }

    // Layer 3D Buildings
    try {
        map.addLayer({
            id: "3d-buildings",
            source: "composite",
            "source-layer": "building",
            filter: ["==", "extrude", "true"],
            type: "fill-extrusion",
            minzoom: 15,
            paint: {
                "fill-extrusion-color": "#cfcfcf",
                "fill-extrusion-height": [
                    "interpolate", ["linear"], ["zoom"],
                    15, 0, 16, ["get", "height"]
                ],
                "fill-extrusion-base": [
                    "interpolate", ["linear"], ["zoom"],
                    15, 0, 16, ["get", "min_height"]
                ],
                "fill-extrusion-opacity": 0.8
            }
        }, labelLayerId);
    } catch (err) {}

    // Layer Traffic Flow
    try {
        map.addSource('mapbox-traffic', {
            type: 'vector',
            url: 'mapbox://mapbox.mapbox-traffic-v1'
        });

        map.addLayer({
            id: 'traffic-flow',
            type: 'line',
            source: 'mapbox-traffic',
            'source-layer': 'traffic',
            layout: {
                'line-cap': 'round',
                'line-join': 'round',
                'visibility': 'visible'
            },
            paint: {
                'line-width': [
                    'interpolate', ['linear'], ['zoom'],
                    14, 3.5, 18, 9
                ],
                'line-color': [
                    'match', ['get', 'congestion'],
                    'low', '#22c55e',
                    'moderate', '#eab308',
                    'heavy', '#f97316',
                    'severe', '#ef4444',
                    '#22c55e'
                ],
                'line-opacity': 0.85
            }
        }, labelLayerId);
    } catch (err) {}

    // Load titik proker dari database
    window.loadLokasiProkja();
});