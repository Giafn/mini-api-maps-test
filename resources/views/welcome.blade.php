<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Jawa Barat GIS PoC</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.3.0/dist/maplibre-gl.css">
    <script src="https://unpkg.com/maplibre-gl@5.3.0/dist/maplibre-gl.js"></script>
    <script src="https://unpkg.com/pmtiles@4.3.0/dist/pmtiles.js"></script>
    <style>
        :root {
            --bg: #06111f;
            --panel: rgba(9, 17, 31, 0.86);
            --panel-strong: rgba(5, 11, 21, 0.96);
            --line: rgba(148, 163, 184, 0.18);
            --text: #e7f1ff;
            --muted: #8fa3bc;
            --accent: #44c58e;
            --accent-2: #58a7ff;
            --warn: #ffbf66;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 15% 18%, rgba(88, 167, 255, 0.13), transparent 30%),
                radial-gradient(circle at 85% 10%, rgba(68, 197, 142, 0.12), transparent 24%),
                linear-gradient(180deg, #06111f 0%, #071522 100%);
        }

        .app {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
            background: rgba(2, 8, 16, 0.82);
            backdrop-filter: blur(14px);
        }

        .brand h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .brand p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .statusbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.04);
            border-radius: 999px;
            font-size: 12px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 18px rgba(68, 197, 142, 0.85);
        }

        .layout {
            min-height: 0;
            display: grid;
            grid-template-columns: 376px minmax(0, 1fr);
            height: 100%;
        }

        .sidebar {
            overflow: auto;
            padding: 16px;
            border-right: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(8, 15, 28, 0.9), rgba(8, 15, 28, 0.72));
        }

        .panel {
            margin-bottom: 14px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.22);
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 14px;
            letter-spacing: .3px;
        }

        .panel small,
        .panel p,
        .meta { color: var(--muted); }

        .stack { display: flex; flex-direction: column; gap: 10px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        label { display: block; font-size: 12px; margin-bottom: 6px; color: #cfe1ff; }
        input, textarea, button {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            padding: 11px 12px;
            outline: none;
        }

        textarea { min-height: 90px; resize: vertical; }
        input:focus, textarea:focus {
            border-color: rgba(88, 167, 255, 0.78);
            box-shadow: 0 0 0 3px rgba(88, 167, 255, 0.16);
        }

        button {
            cursor: pointer;
            font-weight: 700;
            border: none;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            transition: transform .15s ease, opacity .15s ease;
        }

        button:hover { transform: translateY(-1px); }
        button:disabled { opacity: .55; cursor: progress; transform: none; }

        .ghost {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--line);
        }

        .list {
            display: grid;
            gap: 8px;
            font-size: 13px;
        }

        .item {
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.03);
        }

        .map-wrap {
            position: relative;
            min-height: 0;
            height: 100%;
        }
        #map { position: absolute; inset: 0; }

        .overlay {
            position: absolute;
            left: 16px;
            bottom: 16px;
            width: min(440px, calc(100% - 32px));
            z-index: 5;
        }

        .banner {
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(6, 12, 23, 0.86);
            color: var(--text);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.2);
            line-height: 1.45;
            font-size: 13px;
        }

        .banner code {
            padding: 2px 6px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
        }

        .notice {
            margin-top: 8px;
            color: #d8e7ff;
            font-size: 12px;
        }

        .maplibregl-popup-content {
            background: rgba(4, 9, 17, 0.97);
            color: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
        }

        .maplibregl-popup-tip {
            border-top-color: rgba(4, 9, 17, 0.97) !important;
            border-bottom-color: rgba(4, 9, 17, 0.97) !important;
        }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { border-right: none; border-bottom: 1px solid var(--line); }
            .map-wrap { min-height: 72vh; }
        }
    </style>
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="brand">
            <h1>Jawa Barat GIS PoC</h1>
            <p>MapLibre GL JS + PMTiles boundary + point viewport + click-to-add point</p>
        </div>
        <div class="statusbar">
            <span class="pill"><span class="dot"></span><span id="tilesetStatus">Menunggu tileset</span></span>
            <span class="pill">Points <strong id="pointCount">0</strong></span>
            <span class="pill">Viewport <strong id="viewportCount">0</strong></span>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <section class="panel">
                <h2>PMTiles tileset aktif</h2>
                <div class="list" id="tilesetList">
                    <div class="item">Memuat daftar tileset...</div>
                </div>
                <p class="notice" id="tilesetHint">Tileset akan diambil dari <code>/api/pmtiles</code>.</p>
            </section>

            <section class="panel">
                <h2>Import GeoJSON → PMTiles</h2>
                <form id="importForm" class="stack">
                    <div>
                        <label for="tilesetName">Nama tileset</label>
                        <input id="tilesetName" name="name" type="text" value="jawa-barat-boundaries" required>
                    </div>
                    <div>
                        <label for="geojsonFiles">Files GeoJSON / JSON</label>
                        <input id="geojsonFiles" name="files" type="file" accept=".geojson,.json,application/geo+json,application/json" multiple required>
                    </div>
                    <button type="submit" id="importButton">Upload & generate PMTiles</button>
                    <small>Semua file akan dikirim ke <code>/api/pmtiles/import</code>.</small>
                </form>
            </section>

            <section class="panel">
                <h2>Tambah point</h2>
                <form id="pointForm" class="stack">
                    <div>
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" placeholder="Nama point" required>
                    </div>
                    <div>
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Deskripsi panjang point"></textarea>
                    </div>
                    <div class="grid-2">
                        <div>
                            <label for="latitude">Latitude</label>
                            <input id="latitude" name="latitude" type="number" step="any" placeholder="-6.9" required>
                        </div>
                        <div>
                            <label for="longitude">Longitude</label>
                            <input id="longitude" name="longitude" type="number" step="any" placeholder="107.6" required>
                        </div>
                    </div>
                    <button type="submit" id="pointButton">Simpan point</button>
                    <button type="button" class="ghost" id="useMapClickButton">Klik peta untuk isi koordinat</button>
                    <small>Klik peta akan mengisi koordinat dan memberi marker sementara.</small>
                </form>
            </section>

            <section class="panel">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <strong style="font-size:13px">Bbox viewport</strong>
                    <button class="ghost" type="button" id="refreshButton" style="width:auto;padding:7px 10px;">Refresh</button>
                </div>
                <div class="list" style="margin-top:10px;">
                    <div class="item"><strong>North:</strong> <span id="bboxNorth">-</span></div>
                    <div class="item"><strong>South:</strong> <span id="bboxSouth">-</span></div>
                    <div class="item"><strong>East:</strong> <span id="bboxEast">-</span></div>
                    <div class="item"><strong>West:</strong> <span id="bboxWest">-</span></div>
                </div>
            </section>

            <section class="panel">
                <h2>Log</h2>
                <div class="list" id="logList">
                    <div class="item">Siap memuat map.</div>
                </div>
            </section>
        </aside>

        <main class="map-wrap">
            <div id="map"></div>
            <div class="overlay">
                <div class="banner">
                    <strong>Tips:</strong> zoom ke area Jawa Barat untuk memuat point berdasarkan viewport. Klik map untuk menaruh point baru.
                    Boundary layer memakai PMTiles dari API. Source-layer diasumsikan <code>regions</code>.
                </div>
            </div>
        </main>
    </div>
</div>

<script type="module">
    const maplibregl = window.maplibregl;
    const pmtiles = window.pmtiles;

    if (!maplibregl) {
        throw new Error('MapLibre GL JS gagal dimuat dari CDN.');
    }

    if (!pmtiles) {
        throw new Error('PMTiles gagal dimuat dari CDN.');
    }

    const protocol = new pmtiles.Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);

    const apiBase = '/api';
    const state = {
        map: null,
        activeTileset: null,
        sourceUrl: null,
        regionSourceLayer: 'regions',
        pointDraftMarker: null,
        isImporting: false,
        isSavingPoint: false,
        refreshTimer: null,
    };

    const els = {
        tilesetStatus: document.getElementById('tilesetStatus'),
        tilesetList: document.getElementById('tilesetList'),
        tilesetHint: document.getElementById('tilesetHint'),
        pointCount: document.getElementById('pointCount'),
        viewportCount: document.getElementById('viewportCount'),
        bboxNorth: document.getElementById('bboxNorth'),
        bboxSouth: document.getElementById('bboxSouth'),
        bboxEast: document.getElementById('bboxEast'),
        bboxWest: document.getElementById('bboxWest'),
        logList: document.getElementById('logList'),
        importForm: document.getElementById('importForm'),
        pointForm: document.getElementById('pointForm'),
        importButton: document.getElementById('importButton'),
        pointButton: document.getElementById('pointButton'),
        refreshButton: document.getElementById('refreshButton'),
        useMapClickButton: document.getElementById('useMapClickButton'),
        title: document.getElementById('title'),
        description: document.getElementById('description'),
        latitude: document.getElementById('latitude'),
        longitude: document.getElementById('longitude'),
    };

    const baseStyle = {
        version: 8,
        glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
        sources: {
            osm: {
                type: 'raster',
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                attribution: '&copy; OpenStreetMap contributors',
            },
            points: {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] },
            },
        },
        layers: [
            { id: 'osm', type: 'raster', source: 'osm' },
            {
                id: 'points-circle',
                type: 'circle',
                source: 'points',
                paint: {
                    'circle-radius': 7,
                    'circle-color': '#44c58e',
                    'circle-stroke-color': '#06111f',
                    'circle-stroke-width': 2,
                },
            },
            {
                id: 'points-label',
                type: 'symbol',
                source: 'points',
                layout: {
                    'text-field': ['get', 'title'],
                    'text-font': ['Noto Sans Regular'],
                    'text-offset': [0, 1.2],
                    'text-anchor': 'top',
                    'text-size': 12,
                },
                paint: {
                    'text-color': '#eef6ff',
                    'text-halo-color': '#06111f',
                    'text-halo-width': 1.2,
                },
            },
        ],
    };

    function log(message, tone = 'info') {
        const colors = {
            info: 'rgba(255,255,255,0.03)',
            success: 'rgba(68,197,142,0.14)',
            warn: 'rgba(255,191,102,0.14)',
            error: 'rgba(255,107,122,0.14)',
        };

        const node = document.createElement('div');
        node.className = 'item';
        node.style.background = colors[tone] ?? colors.info;
        node.textContent = `${new Date().toLocaleTimeString()} — ${message}`;
        els.logList.prepend(node);
        while (els.logList.children.length > 5) {
            els.logList.removeChild(els.logList.lastElementChild);
        }
    }

    function setStatus(text) {
        els.tilesetStatus.textContent = text;
    }

    function toAbsoluteUrl(url) {
        return new URL(url, window.location.origin).toString();
    }

    function updateBBoxDisplay() {
        const bounds = state.map.getBounds();
        const north = bounds.getNorth().toFixed(6);
        const south = bounds.getSouth().toFixed(6);
        const east = bounds.getEast().toFixed(6);
        const west = bounds.getWest().toFixed(6);

        els.bboxNorth.textContent = north;
        els.bboxSouth.textContent = south;
        els.bboxEast.textContent = east;
        els.bboxWest.textContent = west;

        return { north, south, east, west };
    }

    function removeRegionLayers() {
        ['regions-label', 'regions-line', 'regions-fill'].forEach((layerId) => {
            if (state.map.getLayer(layerId)) {
                state.map.removeLayer(layerId);
            }
        });

        if (state.map.getSource('regions')) {
            state.map.removeSource('regions');
        }
    }

    function ensureRegionSource(tileset) {
        if (!tileset?.url) {
            return;
        }

        const nextSourceUrl = `pmtiles://${toAbsoluteUrl(tileset.url)}`;

        if (state.sourceUrl === nextSourceUrl && state.map.getSource('regions')) {
            return;
        }

        removeRegionLayers();
        state.map.addSource('regions', {
            type: 'vector',
            url: nextSourceUrl,
        });
        state.sourceUrl = nextSourceUrl;
    }

    async function detectRegionSourceLayer(tileset) {
        let nextLayer = 'regions';

        const metadataLayers = tileset?.metadata?.vector_layers;
        if (Array.isArray(metadataLayers) && metadataLayers.length && metadataLayers[0]?.id) {
            return metadataLayers[0].id;
        }

        try {
            const pmtilesUrl = toAbsoluteUrl(tileset.url);
            const archive = new pmtiles.PMTiles(pmtilesUrl);
            protocol.add(archive);
            const metadata = await archive.getMetadata();
            const vectorLayers = metadata?.vector_layers;

            if (typeof vectorLayers === 'string') {
                const parsedLayers = JSON.parse(vectorLayers);
                if (Array.isArray(parsedLayers) && parsedLayers.length && parsedLayers[0]?.id) {
                    nextLayer = parsedLayers[0].id;
                }
            } else if (Array.isArray(vectorLayers) && vectorLayers.length && vectorLayers[0]?.id) {
                nextLayer = vectorLayers[0].id;
            }
        } catch (_error) {
            // Fallback to default source-layer name.
        }

        return nextLayer;
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            headers: { Accept: 'application/json', ...(options.headers || {}) },
            ...options,
        });

        if (!response.ok) {
            throw new Error(await response.text());
        }

        return response.json();
    }

    async function loadTilesets() {
        const payload = await fetchJson(`${apiBase}/pmtiles`);
        const items = payload.data || [];

        if (!items.length) {
            els.tilesetList.innerHTML = '<div class="item">Belum ada tileset. Upload GeoJSON untuk membuat PMTiles.</div>';
            els.tilesetHint.innerHTML = 'Tileset boundary belum tersedia.';
            setStatus('Belum ada tileset');
            state.activeTileset = null;
            state.sourceUrl = null;
            if (state.map) {
                removeRegionLayers();
            }
            return;
        }

        els.tilesetList.innerHTML = '';
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ghost';
            button.style.textAlign = 'left';
            button.style.marginTop = '6px';
            button.innerHTML = `<strong>${item.name}</strong><br><small>${item.filename}</small>`;
            button.addEventListener('click', () => selectTileset(item.id));
            els.tilesetList.appendChild(button);
        });

        if (!state.activeTileset) {
            await selectTileset(items[0].id);
        }
    }

    function ensureRegionLayers() {
        if (!state.map.getSource('regions')) {
            return;
        }

        if (state.map.getLayer('regions-fill')) {
            return;
        }

        state.map.addLayer({
            id: 'regions-fill',
            type: 'fill',
            source: 'regions',
            'source-layer': state.regionSourceLayer,
            paint: {
                'fill-color': [
                    'match',
                    ['get', 'level'],
                    'province', '#3b82f6',
                    'regency', '#44c58e',
                    'district', '#f59e0b',
                    'village', '#ec4899',
                    '#58a7ff',
                ],
                'fill-opacity': [
                    'interpolate',
                    ['linear'],
                    ['zoom'],
                    4, 0.12,
                    8, 0.18,
                    12, 0.24,
                ],
            },
        });

        state.map.addLayer({
            id: 'regions-line',
            type: 'line',
            source: 'regions',
            'source-layer': state.regionSourceLayer,
            paint: {
                'line-color': '#d8eefb',
                'line-width': [
                    'interpolate',
                    ['linear'],
                    ['zoom'],
                    4, 0.6,
                    8, 1.2,
                    12, 2.2,
                ],
                'line-opacity': 0.85,
            },
        });

        state.map.addLayer({
            id: 'regions-label',
            type: 'symbol',
            source: 'regions',
            'source-layer': state.regionSourceLayer,
            layout: {
                'text-field': ['coalesce', ['get', 'name'], ['get', 'code']],
                'text-size': [
                    'interpolate',
                    ['linear'],
                    ['zoom'],
                    5, 10,
                    10, 12,
                    13, 14,
                ],
                'text-anchor': 'center',
                'text-allow-overlap': false,
            },
            paint: {
                'text-color': '#f4fbff',
                'text-halo-color': '#06111f',
                'text-halo-width': 1.6,
            },
        });
    }

    async function selectTileset(id) {
        const detail = await fetchJson(`${apiBase}/pmtiles/${id}`);
        const tileset = detail.data;
        state.activeTileset = tileset;
        state.regionSourceLayer = await detectRegionSourceLayer(tileset);
        setStatus(`${tileset.name} (${tileset.status})`);
        ensureRegionSource(tileset);
        ensureRegionLayers();
        els.tilesetHint.innerHTML = `Tileset aktif: <code>${tileset.filename}</code><br>Boundary layer: <code>${state.regionSourceLayer}</code>.`;
        log(`Tileset aktif: ${tileset.name} (layer: ${state.regionSourceLayer})`, 'success');
    }

    async function loadPoints() {
        const { north, south, east, west } = updateBBoxDisplay();
        const payload = await fetchJson(`${apiBase}/points?north=${north}&south=${south}&east=${east}&west=${west}&limit=500`);
        const points = payload.data?.points || [];

        const features = points.map((point) => ({
            type: 'Feature',
            properties: {
                id: point.id,
                title: point.title,
                description: point.description || '',
            },
            geometry: {
                type: 'Point',
                coordinates: Array.isArray(point.geometry?.coordinates)
                    ? point.geometry.coordinates.map(Number)
                    : [Number(point.longitude), Number(point.latitude)],
            },
        }));

        const source = state.map.getSource('points');
        if (source) {
            source.setData({ type: 'FeatureCollection', features });
        }

        els.pointCount.textContent = String(points.length);
        els.viewportCount.textContent = String(points.length);
        log(`Memuat ${points.length} point dalam viewport.`, 'info');
    }

    function schedulePointsRefresh() {
        clearTimeout(state.refreshTimer);
        state.refreshTimer = setTimeout(() => {
            loadPoints().catch((error) => log(`Gagal memuat points: ${error.message}`, 'error'));
        }, 180);
    }

    function setDraftPoint(lngLat) {
        els.latitude.value = lngLat.lat.toFixed(6);
        els.longitude.value = lngLat.lng.toFixed(6);

        if (state.pointDraftMarker) {
            state.pointDraftMarker.remove();
        }

        const el = document.createElement('div');
        el.style.width = '18px';
        el.style.height = '18px';
        el.style.borderRadius = '50%';
        el.style.border = '3px solid #06111f';
        el.style.background = '#44c58e';
        el.style.boxShadow = '0 0 24px rgba(68, 197, 142, 0.85)';

        state.pointDraftMarker = new maplibregl.Marker({ element: el })
            .setLngLat([lngLat.lng, lngLat.lat])
            .addTo(state.map);

        log(`Koordinat dipilih: ${lngLat.lat.toFixed(6)}, ${lngLat.lng.toFixed(6)}`, 'success');
    }

    async function initMap() {
        state.map = new maplibregl.Map({
            container: 'map',
            style: baseStyle,
            center: [107.6407614, -6.9625217],
            zoom: 15,
            attributionControl: true,
        });

        state.map.addControl(new maplibregl.NavigationControl(), 'top-right');

        state.map.on('load', async () => {
            log('Map loaded.', 'success');
            await loadPoints().catch((error) => log(`Gagal load points awal: ${error.message}`, 'error'));
            await loadTilesets().catch((error) => log(`Gagal load tileset: ${error.message}`, 'error'));
        });

        state.map.on('moveend', schedulePointsRefresh);
        state.map.on('zoomend', schedulePointsRefresh);
        state.map.on('click', (event) => setDraftPoint(event.lngLat));
    }

    els.importForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (state.isImporting) return;

        state.isImporting = true;
        els.importButton.disabled = true;
        els.importButton.textContent = 'Mengupload...';

        try {
            const formData = new FormData(els.importForm);
            const response = await fetch(`${apiBase}/pmtiles/import`, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(await response.text());
            }

            const payload = await response.json();
            log(payload.message || 'Import berhasil', 'success');
            await loadTilesets();
        } catch (error) {
            log(`Import gagal: ${error.message}`, 'error');
        } finally {
            state.isImporting = false;
            els.importButton.disabled = false;
            els.importButton.textContent = 'Upload & generate PMTiles';
        }
    });

    els.pointForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (state.isSavingPoint) return;

        state.isSavingPoint = true;
        els.pointButton.disabled = true;
        els.pointButton.textContent = 'Menyimpan...';

        try {
            const response = await fetch(`${apiBase}/points`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    title: els.title.value,
                    description: els.description.value,
                    latitude: Number(els.latitude.value),
                    longitude: Number(els.longitude.value),
                }),
            });

            if (!response.ok) {
                throw new Error(await response.text());
            }

            const payload = await response.json();
            log(`Point tersimpan dengan id ${payload.data.id}.`, 'success');
            els.pointForm.reset();
            if (state.pointDraftMarker) {
                state.pointDraftMarker.remove();
                state.pointDraftMarker = null;
            }
            await loadPoints();
        } catch (error) {
            log(`Simpan point gagal: ${error.message}`, 'error');
        } finally {
            state.isSavingPoint = false;
            els.pointButton.disabled = false;
            els.pointButton.textContent = 'Simpan point';
        }
    });

    els.refreshButton.addEventListener('click', async () => {
        try {
            await loadPoints();
            await loadTilesets();
            log('Data berhasil di-refresh.', 'success');
        } catch (error) {
            log(`Refresh gagal: ${error.message}`, 'error');
        }
    });

    els.useMapClickButton.addEventListener('click', () => {
        log('Klik peta untuk mengisi koordinat point.', 'info');
    });

    initMap().catch((error) => {
        log(`Inisialisasi map gagal: ${error.message}`, 'error');
        setStatus('Map gagal dimuat');
    });
</script>
</body>
</html>
