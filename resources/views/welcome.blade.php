<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jawa Barat GIS PoC</title>
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.5.0/dist/maplibre-gl.css">
    <script src="https://unpkg.com/maplibre-gl@5.5.0/dist/maplibre-gl.js"></script>
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

        .brand h1 { margin: 0; font-size: 18px; font-weight: 800; }
        .brand p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }

        .statusbar { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }

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
            width: 8px; height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 18px rgba(68, 197, 142, 0.85);
        }

        .layout {
            display: grid;
            grid-template-columns: 376px minmax(0, 1fr);
            height: calc(100vh - 73px);
            overflow: hidden;
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

        .panel h2 { margin: 0 0 10px; font-size: 14px; letter-spacing: .3px; }
        .panel small, .panel p, .meta { color: var(--muted); }

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
            cursor: pointer; font-weight: 700; border: none;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            transition: transform .15s ease, opacity .15s ease;
        }

        button:hover { transform: translateY(-1px); }
        button:disabled { opacity: .55; cursor: progress; transform: none; }

        .ghost {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--line);
        }

        .list { display: grid; gap: 8px; font-size: 13px; }

        .item {
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.03);
        }

        .map-wrap { position: relative; overflow: hidden; }
        #map { position: absolute; inset: 0; }
        #map canvas { outline: none; }

        .overlay {
            position: absolute;
            left: 16px; bottom: 16px;
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
            padding: 2px 6px; border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
        }

        .notice { margin-top: 8px; color: #d8e7ff; font-size: 12px; }

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
                <h2>Basemap / Terrain</h2>
                <div class="stack">
                    <label><input type="radio" name="terrain" value="base-osm"> OpenStreetMap</label>
                    <label><input type="radio" name="terrain" value="base-topo"> OpenTopoMap</label>
                    <label><input type="radio" name="terrain" value="base-carto"> Carto Voyager</label>
                    <label><input type="radio" name="terrain" value="base-esri" checked> Satellite (Esri)</label>
                </div>
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
                        <input id="geojsonFiles" name="files[]" type="file" accept=".geojson,.json,application/geo+json,application/json" multiple required>
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
                    Boundary layer memakai PMTiles dari API. Semua source-layer dimuat otomatis dari metadata.
                </div>
            </div>
        </main>
    </div>
</div>

<script type="module">
    const maplibregl = window.maplibregl;
    const pmtiles = window.pmtiles;

    if (!maplibregl) throw new Error('MapLibre GL JS gagal dimuat dari CDN.');
    if (!pmtiles) throw new Error('PMTiles gagal dimuat dari CDN.');

    // Register protocol sekali saja di level module
    const protocol = new pmtiles.Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);

    const apiBase = '/api';

    const state = {
        map: null,
        activeTilesetId: null,
        regionLayerIds: [],   // track semua layer ID agar bisa dibersihkan
        pointDraftMarker: null,
        isImporting: false,
        isSavingPoint: false,
        refreshTimer: null,
        tileErrorLog: {},
    };

    const els = {
        tilesetStatus:   document.getElementById('tilesetStatus'),
        tilesetList:     document.getElementById('tilesetList'),
        tilesetHint:     document.getElementById('tilesetHint'),
        pointCount:      document.getElementById('pointCount'),
        viewportCount:   document.getElementById('viewportCount'),
        bboxNorth:       document.getElementById('bboxNorth'),
        bboxSouth:       document.getElementById('bboxSouth'),
        bboxEast:        document.getElementById('bboxEast'),
        bboxWest:        document.getElementById('bboxWest'),
        logList:         document.getElementById('logList'),
        importForm:      document.getElementById('importForm'),
        pointForm:       document.getElementById('pointForm'),
        importButton:    document.getElementById('importButton'),
        pointButton:     document.getElementById('pointButton'),
        refreshButton:   document.getElementById('refreshButton'),
        useMapClickButton: document.getElementById('useMapClickButton'),
        title:           document.getElementById('title'),
        description:     document.getElementById('description'),
        latitude:        document.getElementById('latitude'),
        longitude:       document.getElementById('longitude'),
    };

    // Batas tampilan peta untuk menampilkan seluruh Jawa Barat
    const MAP_LIMITS = {
        west: 105.893100,
        east: 109.730890,
        north: -5.462349,
        south: -8.357236,
    };

    const INITIAL_BOUNDS_ARRAY = [[MAP_LIMITS.west, MAP_LIMITS.south], [MAP_LIMITS.east, MAP_LIMITS.north]];
    const SIDEBAR_WIDTH = 376;
    const TILE_ERROR_THRESHOLD = 6; // number of tile errors before fallback
    const TILE_ERROR_WINDOW_MS = 6000; // time window to count errors
    const PROVIDER_TO_LAYER = {
        'opentopomap': 'base-topo',
        'cartocdn.com': 'base-carto',
        'arcgisonline.com': 'base-esri',
        'tile.openstreetmap.org': 'base-osm',
    };

    // ─── Base map style (multiple basemap sources) ───────────────────────────

    const baseStyle = {
        version: 8,
        glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
        sources: {
            osm: {
                type: 'raster',
                tiles: [
                    'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png',
                ],
                tileSize: 256,
                attribution: '&copy; OpenStreetMap contributors',
            },
            topo: {
                type: 'raster',
                tiles: [
                    'https://a.tile.opentopomap.org/{z}/{x}/{y}.png',
                    'https://b.tile.opentopomap.org/{z}/{x}/{y}.png',
                    'https://c.tile.opentopomap.org/{z}/{x}/{y}.png',
                ],
                tileSize: 256,
                attribution: 'Map tiles: © OpenTopoMap (CC-BY-SA)',
            },
            carto: {
                type: 'raster',
                tiles: [
                    'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                    'https://d.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                ],
                tileSize: 256,
                attribution: 'Carto',
            },
            esri: {
                type: 'raster',
                tiles: [
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                ],
                tileSize: 256,
                attribution: 'Esri',
            },
            points: {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] },
                cluster: true,
                clusterRadius: 50,
                clusterMaxZoom: 14,
            },
        },
        layers: [
            // Base raster layers (only one visible at a time)
            { id: 'base-osm', type: 'raster', source: 'osm', layout: { visibility: 'none' } },
            { id: 'base-topo', type: 'raster', source: 'topo', layout: { visibility: 'none' } },
            { id: 'base-carto', type: 'raster', source: 'carto', layout: { visibility: 'none' } },
            { id: 'base-esri', type: 'raster', source: 'esri', layout: { visibility: 'visible' } },

            // Cluster circles
            {
                id: 'clusters',
                type: 'circle',
                source: 'points',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': [
                        'step', ['get', 'point_count'],
                        '#7dd3fc', 10, '#58a7ff', 50, '#2b8cff', 250, '#0d47a1'
                    ],
                    'circle-radius': [
                        'step', ['get', 'point_count'],
                        12, 10, 18, 50, 26, 250, 36
                    ],
                    'circle-opacity': 0.9,
                    'circle-stroke-color': '#06111f',
                    'circle-stroke-width': 1.5,
                },
            },

            // Cluster count label
            {
                id: 'cluster-count',
                type: 'symbol',
                source: 'points',
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': '{point_count_abbreviated}',
                    'text-font': ['Noto Sans Regular'],
                    'text-size': 12,
                },
                paint: {
                    'text-color': '#06111f',
                },
            },

            // Unclustered points (single points)
            {
                id: 'unclustered-point',
                type: 'circle',
                source: 'points',
                filter: ['!', ['has', 'point_count']],
                paint: {
                    'circle-radius': 7,
                    'circle-color': '#c54444',
                    'circle-stroke-color': '#06111f',
                    'circle-stroke-width': 2,
                },
            },

            {
                id: 'unclustered-label',
                type: 'symbol',
                source: 'points',
                filter: ['!', ['has', 'point_count']],
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

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

    function setStatus(text) { els.tilesetStatus.textContent = text; }

    function toAbsoluteUrl(url) {
        return new URL(url, window.location.origin).toString();
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            headers: { Accept: 'application/json', ...(options.headers || {}) },
            ...options,
        });
        if (!response.ok) throw new Error(await response.text());
        return response.json();
    }

    // ─── BBox ────────────────────────────────────────────────────────────────

    function updateBBoxDisplay() {
        const bounds = state.map.getBounds();
        const north = bounds.getNorth().toFixed(6);
        const south = bounds.getSouth().toFixed(6);
        const east  = bounds.getEast().toFixed(6);
        const west  = bounds.getWest().toFixed(6);
        els.bboxNorth.textContent = north;
        els.bboxSouth.textContent = south;
        els.bboxEast.textContent  = east;
        els.bboxWest.textContent  = west;
        return { north, south, east, west };
    }

    // ─── Region layers ────────────────────────────────────────────────────────

    /**
     * Hapus semua layer & source regions yang sebelumnya ditambahkan.
     * Bergantung pada state.regionLayerIds untuk tracking.
     */
    function removeRegionLayers() {
        for (const layerId of state.regionLayerIds) {
            if (state.map.getLayer(layerId)) {
                state.map.removeLayer(layerId);
            }
        }
        state.regionLayerIds = [];

        if (state.map.getSource('regions')) {
            state.map.removeSource('regions');
        }
    }

    /**
     * Tunggu hingga source tertentu benar-benar loaded oleh MapLibre.
     * Menggunakan event 'sourcedata' agar tidak ada race condition.
     */
    function waitForSourceLoaded(sourceId) {
        return new Promise((resolve) => {
            // Jika sudah loaded sebelum listener terpasang, selesaikan langsung
            if (state.map.isSourceLoaded(sourceId)) {
                resolve();
                return;
            }

            function onSourceData(e) {
                if (e.sourceId === sourceId && state.map.isSourceLoaded(sourceId)) {
                    state.map.off('sourcedata', onSourceData);
                    resolve();
                }
            }

            state.map.on('sourcedata', onSourceData);
        });
    }

    /**
     * Tambahkan fill + line layer untuk setiap vector_layer di metadata PMTiles.
     * Tidak ada hardcode source-layer — semua dibaca dari metadata.
     */
    function addRegionLayers(vectorLayers) {
        if (!Array.isArray(vectorLayers) || vectorLayers.length === 0) {
            log('PMTiles tidak memiliki vector_layers di metadata.', 'warn');
            return;
        }

        // Cari layer point yang tersedia untuk menjadi referensi insert-before.
        const preferredPointLayers = ['clusters', 'unclustered-point', 'cluster-count', 'unclustered-label'];
        const insertBefore = preferredPointLayers.find((id) => state.map.getLayer(id));

        for (const layer of vectorLayers) {
            if (!layer?.id) continue;
            const fillId = `regions-fill-${layer.id}`;
            const lineId = `regions-line-${layer.id}`;

            const fillLayer = {
                id: fillId,
                type: 'fill',
                source: 'regions',
                'source-layer': layer.id,
                paint: {
                    // invisible fill used only for hit-testing / selecting features
                    'fill-color': '#000000',
                    'fill-opacity': 0,
                },
            };

            const lineLayer = {
                id: lineId,
                type: 'line',
                source: 'regions',
                'source-layer': layer.id,
                paint: {
                    'line-color': '#040055',
                    'line-width': [
                        'interpolate', ['linear'], ['zoom'],
                        4, 0.8,
                        8, 1.4,
                        12, 2.2,
                    ],
                    'line-opacity': 0.9,
                },
            };

            // Tambahkan fill terlebih dahulu (invisible), lalu line agar border terlihat di atasnya.
            if (insertBefore) {
                state.map.addLayer(fillLayer, insertBefore);
                state.map.addLayer(lineLayer, insertBefore);
            } else {
                state.map.addLayer(fillLayer);
                state.map.addLayer(lineLayer);
            }

            // Daftarkan agar bisa dibersihkan nanti (isi fill dulu agar queryRenderedFeatures mudah)
            state.regionLayerIds.push(fillId, lineId);

            log(`Source-layer dimuat: ${layer.id}`, 'success');
        }

        log(`Total ${vectorLayers.length} source-layer (line only) ditambahkan.`, 'success');
    }

    // ─── Tileset ──────────────────────────────────────────────────────────────

    async function selectTileset(id) {
        if (state.activeTilesetId === id) return;

        try {
            setStatus('Memuat tileset...');
            const detail = await fetchJson(`${apiBase}/pmtiles/${id}`);
            const tileset = detail.data;

            const pmtilesUrl = toAbsoluteUrl(tileset.url);

            // Buat instance PMTiles & daftarkan ke protocol
            const archive = new pmtiles.PMTiles(pmtilesUrl);
            protocol.add(archive);

            // Bersihkan layer & source lama
            removeRegionLayers();

            // Daftarkan source baru ke map
            state.map.addSource('regions', {
                type: 'vector',
                url: `pmtiles://${pmtilesUrl}`,
            });

            // Tunggu source benar-benar loaded sebelum addLayer
            await waitForSourceLoaded('regions');

            // Baca metadata untuk mendapatkan daftar vector_layers
            const metadata = await archive.getMetadata();
            console.log('[PMTiles metadata]', metadata);

            // vector_layers bisa ada di root atau di dalam json string
            let vectorLayers = metadata?.vector_layers ?? [];

            // Beberapa tools menyimpan vector_layers sebagai string JSON
            if (typeof vectorLayers === 'string') {
                try { vectorLayers = JSON.parse(vectorLayers); } catch { vectorLayers = []; }
            }

            if (!Array.isArray(vectorLayers)) vectorLayers = [];

            // Tambahkan layer untuk setiap source-layer di metadata
            addRegionLayers(vectorLayers);

            state.activeTilesetId = id;
            setStatus(`Aktif: ${tileset.name}`);
            log(`Tileset aktif: ${tileset.name} (${vectorLayers.length} layer)`, 'success');
        } catch (err) {
            log(`Gagal memuat tileset: ${err.message}`, 'error');
            setStatus('Gagal memuat tileset');
        }
    }

    async function loadTilesets() {
        const payload = await fetchJson(`${apiBase}/pmtiles`);
        const items = payload.data || [];

        if (!items.length) {
            els.tilesetList.innerHTML = '<div class="item">Belum ada tileset. Upload GeoJSON untuk membuat PMTiles.</div>';
            els.tilesetHint.innerHTML = 'Tileset boundary belum tersedia.';
            setStatus('Belum ada tileset');
            state.activeTilesetId = null;
            if (state.map) removeRegionLayers();
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

        // Auto-pilih tileset pertama jika belum ada yang aktif
        if (!state.activeTilesetId) {
            await selectTileset(items[0].id);
        }
    }

    // ─── Points ───────────────────────────────────────────────────────────────

    async function loadPoints() {
        const { north, south, east, west } = updateBBoxDisplay();
        const payload = await fetchJson(
            `${apiBase}/points?north=${north}&south=${south}&east=${east}&west=${west}&limit=500`
        );
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
        if (source) source.setData({ type: 'FeatureCollection', features });

        els.pointCount.textContent   = String(points.length);
        els.viewportCount.textContent = String(points.length);
        log(`${points.length} point dimuat di viewport.`, 'info');
    }

    function schedulePointsRefresh() {
        clearTimeout(state.refreshTimer);
        state.refreshTimer = setTimeout(() => {
            loadPoints().catch((err) => log(`Gagal memuat points: ${err.message}`, 'error'));
        }, 180);
    }

    // ─── Draft marker ─────────────────────────────────────────────────────────

    function setDraftPoint(lngLat) {
        els.latitude.value  = lngLat.lat.toFixed(6);
        els.longitude.value = lngLat.lng.toFixed(6);

        // Reset title/description by default
        els.title.value = '';
        els.description.value = '';

        // Try to prefill title/description from region feature properties at clicked point
        try {
            const pt = state.map.project([lngLat.lng, lngLat.lat]);
            const features = state.map.queryRenderedFeatures(pt, { layers: state.regionLayerIds });
            if (features && features.length) {
                // Prefer common name-like properties
                const nameKeys = [
                    'WADMKC','WADMKK','WADMPR','METADATA','KDCPUM','KDPKAB','KDPPUM',
                    'title','name','nama','Nama','NAME','label','display_name','nama_lengkap','display'
                ];
                const descKeys = ['description','desc','keterangan','notes','note','METADATA','UPDATED','metadata','updated'];

                let chosenProps = null;
                for (const f of features) {
                    const p = f.properties || {};
                    for (const k of nameKeys) {
                        if (p[k]) { chosenProps = p; break; }
                    }
                    if (chosenProps) break;
                }

                // fallback to first feature props if none matched
                if (!chosenProps) chosenProps = features[0].properties || {};

                // Prefer readable admin names if available (subdistrict + city)
                if (chosenProps.WADMKC) {
                    const city = chosenProps.WADMKK || chosenProps.WADMPR || '';
                    els.title.value = String(chosenProps.WADMKC) + (city ? ', ' + String(city) : '');
                } else {
                    for (const k of nameKeys) {
                        if (chosenProps[k]) { els.title.value = String(chosenProps[k]).trim(); break; }
                    }
                }

                for (const k of descKeys) {
                    if (chosenProps[k]) { els.description.value = String(chosenProps[k]).trim(); break; }
                }
            }
        } catch (e) {
            // non-fatal
            console.debug('Prefill properties failed', e);
        }

        if (state.pointDraftMarker) state.pointDraftMarker.remove();

        const el = document.createElement('div');
        el.style.cssText = [
            'width:18px', 'height:18px', 'border-radius:50%',
            'border:3px solid #06111f', 'background:#44c58e',
            'box-shadow:0 0 24px rgba(68,197,142,0.85)',
        ].join(';');

        state.pointDraftMarker = new maplibregl.Marker({ element: el })
            .setLngLat([lngLat.lng, lngLat.lat])
            .addTo(state.map);

        log(`Koordinat dipilih: ${lngLat.lat.toFixed(6)}, ${lngLat.lng.toFixed(6)}`, 'success');
    }

    // ─── Map init ─────────────────────────────────────────────────────────────

    async function initMap() {
        state.map = new maplibregl.Map({
            container: 'map',
            style: baseStyle,
            // center set roughly to middle of provided bounds; fitBounds will adjust zoom
            center: [ (MAP_LIMITS.west + MAP_LIMITS.east) / 2, (MAP_LIMITS.north + MAP_LIMITS.south) / 2 ],
            zoom: 8,
            // Batasi seberapa jauh user bisa zoom untuk mencegah permintaan tile yang terlalu tinggi
            maxZoom: 16,
            // Batasi panning agar user tidak keluar dari area Jawa Barat
            maxBounds: INITIAL_BOUNDS_ARRAY,
            attributionControl: true,
        });

        state.map.addControl(new maplibregl.NavigationControl(), 'top-right');

        // Do not auto-switch terrain on tile/network errors; only log for debugging
        state.map.on('error', (e) => {
            try {
                console.debug('Map tile/network error (auto-fallback disabled):', e);
            } catch (err) {
                // ignore
            }
        });

        state.map.on('load', async () => {
            log('Map loaded.', 'success');

            // Pastikan tampilan awal menampilkan seluruh bounding box Jawa Barat
            try {
                const padding = { left: SIDEBAR_WIDTH + 16, right: 16, top: 16, bottom: 16 };
                state.map.fitBounds(INITIAL_BOUNDS_ARRAY, { padding, duration: 0 });
                // setMaxBounds agar panning dibatasi baik saat resize maupun gesture
                state.map.setMaxBounds(INITIAL_BOUNDS_ARRAY);
            } catch (e) {
                // Non-fatal
                console.warn('Gagal fitBounds pada inisialisasi:', e);
            }

            await loadPoints().catch((err) => log(`Gagal load points awal: ${err.message}`, 'error'));
            await loadTilesets().catch((err) => log(`Gagal load tileset: ${err.message}`, 'error'));

            // Apply selected basemap (if any)
            try {
                const sel = document.querySelector('input[name="terrain"]:checked');
                if (sel) setTerrain(sel.value);
                else setTerrain('base-esri');
            } catch (e) { /* non-fatal */ }

            // Cluster interactions
            state.map.on('click', 'clusters', (e) => {
                const features = state.map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                if (!features.length) return;
                const clusterId = features[0].properties.cluster_id;
                state.map.getSource('points').getClusterExpansionZoom(clusterId, (err, zoom) => {
                    if (err) { log(`Cluster expansion failed: ${err.message}`, 'error'); return; }
                    state.map.easeTo({ center: features[0].geometry.coordinates, zoom });
                });
            });

            state.map.on('mouseenter', 'clusters', () => { state.map.getCanvas().style.cursor = 'pointer'; });
            state.map.on('mouseleave', 'clusters', () => { state.map.getCanvas().style.cursor = ''; });

            state.map.on('click', 'unclustered-point', (e) => {
                const f = e.features && e.features[0];
                if (!f) return;
                const coords = f.geometry.coordinates.slice();
                const title = f.properties && f.properties.title ? f.properties.title : '';
                const desc = f.properties && f.properties.description ? f.properties.description : '';
                new maplibregl.Popup().setLngLat(coords).setHTML(`<strong>${title}</strong><div>${desc}</div>`).addTo(state.map);
            });
        });

        state.map.on('moveend', schedulePointsRefresh);
        state.map.on('zoomend', schedulePointsRefresh);
        // Only set draft point when user clicks on empty map (not on cluster/point)
        state.map.on('click', (event) => {
            const features = state.map.queryRenderedFeatures(event.point, { layers: ['clusters', 'unclustered-point'] });
            if (features.length) return;
            setDraftPoint(event.lngLat);
        });
    }

    // ─── Form handlers ────────────────────────────────────────────────────────

    els.importForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.isImporting) return;
        state.isImporting = true;
        els.importButton.disabled = true;
        els.importButton.textContent = 'Mengupload...';

        try {
            const response = await fetch(`${apiBase}/pmtiles/import`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body: new FormData(els.importForm),
            });
            if (!response.ok) throw new Error(await response.text());
            const payload = await response.json();
            log(payload.message || 'Import berhasil', 'success');

            // Reset agar tileset baru bisa auto-select
            state.activeTilesetId = null;
            await loadTilesets();
        } catch (err) {
            log(`Import gagal: ${err.message}`, 'error');
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
                    latitude:  Number(els.latitude.value),
                    longitude: Number(els.longitude.value),
                }),
            });
            if (!response.ok) throw new Error(await response.text());
            const payload = await response.json();
            log(`Point tersimpan: id ${payload.data.id}`, 'success');
            els.pointForm.reset();
            if (state.pointDraftMarker) {
                state.pointDraftMarker.remove();
                state.pointDraftMarker = null;
            }
            await loadPoints();
        } catch (err) {
            log(`Simpan point gagal: ${err.message}`, 'error');
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
        } catch (err) {
            log(`Refresh gagal: ${err.message}`, 'error');
        }
    });

    els.useMapClickButton.addEventListener('click', () => {
        log('Klik peta untuk mengisi koordinat point.', 'info');
    });

    // Basemap / terrain switching
    const BASE_LAYERS = ['base-osm', 'base-topo', 'base-carto', 'base-esri'];

    function setTerrain(layerId) {
        state.selectedTerrain = layerId;
        if (!state.map) return;
        for (const lid of BASE_LAYERS) {
            if (!state.map.getLayer(lid)) continue;
            const vis = (lid === layerId) ? 'visible' : 'none';
            try { state.map.setLayoutProperty(lid, 'visibility', vis); } catch (e) { /* ignore */ }
        }
        const labelMap = {
            'base-osm': 'OpenStreetMap',
            'base-topo': 'OpenTopoMap',
            'base-carto': 'Carto Voyager',
            'base-esri': 'Esri Satellite',
        };
        log(`Basemap dipilih: ${labelMap[layerId] || layerId}`, 'info');
    }

    // Attach radio listeners
    document.querySelectorAll('input[name="terrain"]').forEach((el) => {
        el.addEventListener('change', (e) => {
            if (e.target.checked) setTerrain(e.target.value);
        });
    });

    initMap().catch((err) => {
        log(`Inisialisasi map gagal: ${err.message}`, 'error');
        setStatus('Map gagal dimuat');
    });
</script>
</body>
</html>