<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Moon Group | Índice de Presión sobre el Bosque</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
  :root{--blue:#1F82DB;--navy:#155E9C;--sky:#9CC8E4;--orange:#EB9250;--red:#C94A3A;--bg:#EEF2F5;--ink:#17324D;--muted:#4F5B66;--line:#e7eef4}
  *{box-sizing:border-box}
  [hidden]{display:none!important}
  body{margin:0;font-family:Arial,sans-serif;background:var(--bg);color:var(--ink)}
  aside{width:250px;position:fixed;height:100vh;background:var(--navy);padding:24px;color:#fff;overflow:auto}
  .brand{background:#fff;border-radius:12px;padding:12px}
  .brand img{max-width:100%;max-height:70px;display:block;margin:auto}
  aside h3{font-size:17px;margin:26px 0 8px}
  aside p{font-size:13px;color:#d9efff;line-height:1.5}
  main{margin-left:250px;padding:30px;max-width:1500px}
  h1{margin:0;color:var(--navy);font-size:29px}
  .sub{margin:6px 0 22px;color:var(--muted);font-size:14px}
  .filters,.cards,.row{display:flex;flex-wrap:wrap;gap:15px}
  select,.card,.panel{background:#fff;border:0;border-radius:12px;box-shadow:0 2px 10px #17324d14}
  select{padding:12px;min-width:200px;font-size:14px;color:var(--ink)}
  .cards{margin:18px 0}
  .card{padding:16px 20px;min-width:160px;font-size:13px;color:var(--muted);flex:1}
  .big{display:block;font-weight:700;font-size:28px;color:var(--navy);margin-bottom:4px}
  .panel{padding:18px;flex:1;min-width:340px}
  .panel h2{font-size:16px;margin:0 0 14px;color:var(--navy)}
  .panel .hint{font-size:12px;color:var(--muted);margin:-8px 0 12px}
  .stack{margin-top:15px}
  .bars{display:grid;gap:11px}
  .barrow{display:grid;grid-template-columns:180px 1fr 40px;gap:10px;align-items:center;font-size:13px}
  .track{height:12px;background:#e8eef3;border-radius:8px;overflow:hidden}
  .fill{height:100%;border-radius:8px}
  .levels{display:flex;gap:14px;align-items:flex-end;height:194px;border-bottom:1px solid #d7e1eb;padding:12px 8px}
  .level{width:82px;text-align:center;font-size:12px;color:var(--muted);display:flex;flex-direction:column;justify-content:flex-end;height:100%}
  .col{width:100%;border-radius:8px 8px 0 0;min-height:2px;margin-bottom:6px}
  .map-layout{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:15px}
  .mapbox{position:relative;height:460px;background:linear-gradient(135deg,#e8f5ff,#f7fbfd);border-radius:10px;overflow:hidden;border:1px solid #d7e8f3}
  .overview{position:relative;height:420px;border-radius:10px;overflow:hidden;border:1px solid #d7e8f3;background:#e8f5ff;z-index:0}
  .overview .map-message{z-index:500;background:#f7fbfd}
  .leaflet-popup-content{font-size:12px;line-height:1.45;color:var(--ink)}
  .leaflet-popup-content b{color:var(--navy)}
  .mapbox iframe{width:100%;height:100%;border:0;display:block}
  .map-message{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;color:var(--muted);font-size:14px}
  .points{display:grid;gap:8px;max-height:460px;overflow:auto;align-content:start}
  .point{display:grid;grid-template-columns:14px 1fr;gap:10px;align-items:center;text-align:left;background:#f7fbfd;border:1px solid #d7e8f3;border-radius:10px;padding:10px;font:inherit;font-size:12px;color:var(--ink);cursor:pointer}
  .point:hover,.point.active{border-color:var(--blue);background:#eaf4fd}
  .point b{display:block;font-size:13px}
  .dot{width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px #0004}
  .legend{font-size:12px;color:var(--muted);margin:10px 0 0;display:flex;flex-wrap:wrap;gap:14px;align-items:center}
  .legend span{display:inline-flex;align-items:center;gap:6px}
  .legend a{color:var(--blue)}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{text-align:left;color:var(--navy);border-bottom:2px solid var(--sky);padding:10px;white-space:nowrap}
  td{padding:10px;border-bottom:1px solid var(--line);vertical-align:top}
  td:first-child{white-space:nowrap}
  td:nth-child(2){min-width:160px}
  td small{display:block;color:var(--muted);font-size:11px;margin-top:3px;max-width:220px}
  tbody tr{cursor:pointer}
  tbody tr:hover{background:#f5f9fc}
  .scroll{overflow:auto}
  .badge{display:inline-block;font-weight:700;border-radius:6px;padding:2px 8px;color:#fff;font-size:12px}
  .notice{background:#fff7ed;border:1px solid #f6d3b4;color:#8a4b17;border-radius:10px;padding:10px 14px;font-size:13px;margin:0 0 15px}
  .empty{color:var(--muted);font-size:14px;padding:12px 0}
  .formula{background:#f3f8fc;border-left:4px solid var(--blue);border-radius:8px;padding:12px 14px;font-size:14px;margin:0 0 14px}
  .ranges{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
  .range{border-radius:10px;padding:10px 14px;font-size:13px;color:#fff;min-width:150px}
  .range b{display:block;font-size:15px}
  @media(max-width:1000px){.map-layout{grid-template-columns:1fr}.points{max-height:none;grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}}
  @media(max-width:760px){aside{position:static;width:100%;height:auto}main{margin:0;padding:18px 16px}.panel{min-width:100%}select{min-width:100%}.barrow{grid-template-columns:120px 1fr 36px}.mapbox,.overview{height:360px}}
</style>
</head>
<body>
<aside>
    <div class="brand"><img src="{{ asset('images/moon-group-logo.png') }}" alt="Moon Group"></div>
    <h3>Presión sobre el Bosque</h3>
    <p><strong>“Comprender para conservar”</strong></p>
    <p>Herramienta de diagnóstico que analiza disponibilidad de leña, distancia de recolección, perturbación, acceso y aprovechamiento forestal. Genera un índice que ayuda a comprender la presión de los hogares sobre el bosque.</p>
    <p>Cada factor se valora de 1 a 3; el índice es su promedio.</p>
</aside>
<main>
    <h1>Dashboard de Índice de Presión sobre el Bosque</h1>
    <p class="sub">Resultados de la Encuesta de Presión sobre el Bosque{{ $dataset['project'] ? ' · '.$dataset['project']['name'] : '' }} · Metodología {{ config('forest_pressure.methodology') }}</p>

    @foreach ($dataset['warnings'] as $warning)
        <p class="notice">{{ $warning }}</p>
    @endforeach

    <div class="filters">
        <select id="dep" aria-label="Departamento"></select>
        <select id="pro" aria-label="Provincia"></select>
        <select id="dis" aria-label="Distrito"></select>
    </div>

    <div class="cards">
        <div class="card"><span class="big" id="n">–</span>Respuestas evaluadas</div>
        <div class="card"><span class="big" id="ipb">–</span>IPB promedio</div>
        <div class="card"><span class="big" id="nivel">–</span>Nivel de presión</div>
        <div class="card"><span class="big" id="altas">–</span>Zonas con presión alta</div>
    </div>

    <div class="row">
        <section class="panel">
            <h2>Nivel promedio por factor</h2>
            <div class="bars" id="barras"></div>
        </section>
        <section class="panel">
            <h2>Distribución del nivel de presión</h2>
            <div class="levels" id="niveles"></div>
        </section>
    </div>

    <section class="panel stack">
        <h2>Mapa general de las zonas evaluadas</h2>
        <p class="hint">Cada punto es una respuesta, con el color de su nivel de presión. Haga clic en un punto para verlo en el visor GeoBosques.</p>
        <div class="overview" id="mapa-general">
            <div class="map-message" id="mapa-general-mensaje" hidden></div>
        </div>
        <div class="legend">
            @foreach ($levels as $level)
                <span><i class="dot" style="background:{{ ['BAJA' => '#1F82DB', 'MEDIA' => '#EB9250', 'ALTA' => '#C94A3A'][$level['code']] }}"></i>Presión {{ Str::lower($level['label']) }}</span>
            @endforeach
        </div>
    </section>

    <section class="panel stack">
        <h2>Ubicación de la zona en GeoBosques</h2>
        <p class="hint">Visor GeoBosques (MINAM). Seleccione una zona en el mapa general, en la lista o en la tabla.</p>
        <div class="map-layout">
            <div class="mapbox">
                <iframe id="mapa" title="Ubicación de la zona en el visor GeoBosques" referrerpolicy="no-referrer-when-downgrade" hidden></iframe>
                <div class="map-message" id="mapa-mensaje">No hay zonas con coordenadas para mostrar.</div>
            </div>
            <div class="points" id="puntos"></div>
        </div>
        <div class="legend" id="leyenda"></div>
    </section>

    <section class="panel stack">
        <h2>Detalle de evaluación por zona</h2>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th>Código</th><th>Ubicación</th><th>Comunidad</th>
                    @foreach ($factors as $factor)
                        <th>{{ $factor['label'] }}</th>
                    @endforeach
                    <th>IPB</th><th>Nivel</th>
                </tr>
                </thead>
                <tbody id="tabla"></tbody>
            </table>
        </div>
        <p class="empty" id="vacio" hidden>No hay respuestas finalizadas para los filtros seleccionados.</p>
    </section>

    <section class="panel stack">
        <h2>Cálculo del índice</h2>
        <p class="formula"><strong>IPB</strong> = (Accesibilidad + Actividad agrícola + Asentamientos humanos + Aprovechamiento forestal + Perturbación del bosque) ÷ 5</p>
        <div class="scroll">
            <table>
                <thead>
                <tr><th>Factor</th><th>Indicador</th><th>Nivel 1 – Bajo</th><th>Nivel 2 – Medio</th><th>Nivel 3 – Alto</th></tr>
                </thead>
                <tbody>
                @foreach ($factors as $factor)
                    <tr>
                        <td><strong>{{ $factor['label'] }}</strong></td>
                        <td>{{ $factor['indicator'] }}</td>
                        @foreach ($factor['criteria'] as $criterion)
                            <td>{{ $criterion }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="ranges" id="rangos"></div>
        <p class="hint" style="margin:14px 0 0">Los umbrales de distancia son criterios operativos para un estudio básico; no son umbrales normativos oficiales. Solo se incluyen participaciones finalizadas con los cinco factores respondidos{{ $dataset['pending_drafts'] > 0 ? ' ('.$dataset['pending_drafts'].' en borrador pendientes)' : '' }}.</p>
    </section>
</main>

@php
    $factorLabels = array_map(fn ($factor) => ['key' => $factor['key'], 'label' => $factor['label']], $factors);
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(() => {
    const ZONES = @json($dataset['zones']);
    const FACTORS = @json($factorLabels);
    const LEVELS = @json($levels);
    const GEOBOSQUES = @json($geobosques);
    const COLORS = {BAJA: '#1F82DB', MEDIA: '#EB9250', ALTA: '#C94A3A'};
    const $ = id => document.getElementById(id);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const levelOf = value => (LEVELS.find(level => value <= level.max) || LEVELS[LEVELS.length - 1]).code;
    const labelOf = code => (LEVELS.find(level => level.code === code) || {label: code}).label;
    const hasCoordinates = zone => zone.latitude !== null && zone.longitude !== null;
    let selectedId = null;

    function options(id, values, label) {
        const select = $(id);
        const previous = select.value;
        select.innerHTML = '<option value="Todos">' + label + ': Todos</option>'
            + [...new Set(values)].sort((a, b) => a.localeCompare(b, 'es'))
                .map(value => '<option value="' + esc(value) + '">' + esc(value) + '</option>').join('');
        if ([...select.options].some(option => option.value === previous)) {
            select.value = previous;
        }
    }

    function selection() {
        const d = $('dep').value, p = $('pro').value, t = $('dis').value;
        return ZONES.filter(zone => (d === 'Todos' || zone.department === d)
            && (p === 'Todos' || zone.province === p)
            && (t === 'Todos' || zone.district === t));
    }

    function average(list, read) {
        return list.length ? list.reduce((sum, item) => sum + read(item), 0) / list.length : 0;
    }

    let overview = null, markers = null;

    function overviewMessage(text) {
        const message = $('mapa-general-mensaje');
        message.textContent = text;
        message.hidden = !text;
    }

    function drawOverview(located) {
        if (typeof L === 'undefined') {
            overviewMessage('No se pudo cargar el mapa general. Verifique la conexión a internet.');
            return;
        }
        if (!overview) {
            const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Imágenes &copy; Esri'
            });
            overview = L.map('mapa-general', {layers: [satellite], scrollWheelZoom: false}).setView([-9.19, -75.02], 5);
            L.control.layers({'Satélite': satellite, 'Mapa': streets}).addTo(overview);
            markers = L.featureGroup().addTo(overview);
        }
        markers.clearLayers();
        overviewMessage(located.length ? '' : 'No hay zonas con coordenadas para mostrar.');
        located.forEach(zone => {
            L.circleMarker([zone.latitude, zone.longitude], {
                radius: 9, color: '#fff', weight: 3, fillColor: COLORS[zone.level], fillOpacity: 1
            })
                .bindPopup('<b>' + esc(zone.code) + '</b><br>' + esc([zone.community, zone.district, zone.province].filter(Boolean).join(', '))
                    + '<br>IPB <b>' + zone.ipb.toFixed(2) + '</b> · ' + esc(labelOf(zone.level)))
                .on('click', () => selectZone(zone.id))
                .addTo(markers);
        });
        if (located.length === 1) {
            overview.setView([located[0].latitude, located[0].longitude], 12);
        } else if (located.length > 1) {
            overview.fitBounds(markers.getBounds(), {padding: [40, 40], maxZoom: 14});
        }
    }

    function selectZone(id) {
        const zone = ZONES.find(item => item.id === id && hasCoordinates(item));
        selectedId = zone ? zone.id : null;
        document.querySelectorAll('.point').forEach(button => button.classList.toggle('active', Number(button.dataset.id) === selectedId));
        const frame = $('mapa'), message = $('mapa-mensaje'), legend = $('leyenda');
        if (!zone) {
            frame.hidden = true;
            frame.removeAttribute('src');
            message.hidden = false;
            message.textContent = 'No hay zonas con coordenadas para mostrar.';
            legend.innerHTML = '';
            return;
        }
        const url = GEOBOSQUES.base_url + '?' + GEOBOSQUES.marker_parameter + '=' + zone.latitude + ',' + zone.longitude;
        legend.innerHTML = '<span><i class="dot" style="background:' + COLORS[zone.level] + '"></i>'
            + esc(zone.code + ' · ' + zone.district + ' · IPB ' + zone.ipb.toFixed(2) + ' (' + labelOf(zone.level) + ')') + '</span>'
            + '<span>Lat ' + zone.latitude + ', Lon ' + zone.longitude + '</span>'
            + '<a href="' + esc(url) + '" target="_blank" rel="noopener">Abrir en GeoBosques</a>';
        if (!navigator.onLine) {
            frame.hidden = true;
            message.hidden = false;
            message.textContent = 'Sin conexión: el visor GeoBosques no está disponible.';
            return;
        }
        if (frame.getAttribute('src') !== url) {
            frame.src = url;
        }
        frame.hidden = false;
        message.hidden = true;
    }

    function draw() {
        const zones = selection();
        const mean = average(zones, zone => zone.ipb);
        const level = levelOf(mean);
        $('n').textContent = zones.length;
        $('ipb').textContent = zones.length ? mean.toFixed(2) : '–';
        $('nivel').textContent = zones.length ? labelOf(level).toUpperCase() : '–';
        $('nivel').style.color = zones.length ? COLORS[level] : '';
        $('altas').textContent = zones.length ? zones.filter(zone => zone.level === 'ALTA').length : '–';

        $('barras').innerHTML = FACTORS.map(factor => {
            const value = average(zones, zone => zone.factors[factor.key].level);
            return '<div class="barrow"><span>' + esc(factor.label) + '</span><div class="track"><div class="fill" style="width:'
                + (value / 3 * 100) + '%;background:' + COLORS[levelOf(value)] + '"></div></div><b>'
                + (zones.length ? value.toFixed(2) : '–') + '</b></div>';
        }).join('');

        const counts = LEVELS.map(item => zones.filter(zone => zone.level === item.code).length);
        const highest = Math.max(1, ...counts);
        $('niveles').innerHTML = LEVELS.map((item, index) => '<div class="level"><div class="col" style="height:'
            + (counts[index] / highest * 140) + 'px;background:' + COLORS[item.code] + '"></div><b>'
            + counts[index] + '</b>' + item.code + '</div>').join('');

        const located = zones.filter(hasCoordinates);
        drawOverview(located);
        $('puntos').innerHTML = located.map(zone => '<button type="button" class="point" data-id="' + zone.id + '">'
            + '<i class="dot" style="background:' + COLORS[zone.level] + '"></i><span><b>' + esc(zone.code) + ' · IPB '
            + zone.ipb.toFixed(2) + '</b>' + esc([zone.community, zone.district].filter(Boolean).join(' · ')) + '</span></button>').join('');
        document.querySelectorAll('.point').forEach(button => button.onclick = () => selectZone(Number(button.dataset.id)));
        selectZone(located.some(zone => zone.id === selectedId) ? selectedId : (located[0] || {}).id);

        $('tabla').innerHTML = zones.map(zone => '<tr data-id="' + zone.id + '"><td><b>' + esc(zone.code) + '</b><small>' + esc(zone.date || '') + '</small></td>'
            + '<td>' + esc(zone.district + ', ' + zone.province + ', ' + zone.department) + '</td>'
            + '<td>' + esc(zone.community || '–') + '</td>'
            + FACTORS.map(factor => {
                const item = zone.factors[factor.key];
                return '<td><b>' + item.level + '</b><small>' + esc(item.value || '') + '</small></td>';
            }).join('')
            + '<td><b>' + zone.ipb.toFixed(2) + '</b></td>'
            + '<td><span class="badge" style="background:' + COLORS[zone.level] + '">' + zone.level + '</span>'
            + (zone.high_factors.length ? '<small>Nivel 3: ' + esc(zone.high_factors.join(', ')) + '</small>' : '') + '</td></tr>').join('');
        document.querySelectorAll('#tabla tr').forEach(row => row.onclick = () => {
            selectZone(Number(row.dataset.id));
            $('mapa').scrollIntoView({behavior: 'smooth', block: 'center'});
        });
        $('vacio').hidden = zones.length > 0;
    }

    function change() {
        const byDepartment = ZONES.filter(zone => $('dep').value === 'Todos' || zone.department === $('dep').value);
        options('pro', byDepartment.map(zone => zone.province), 'Provincia');
        const byProvince = byDepartment.filter(zone => $('pro').value === 'Todos' || zone.province === $('pro').value);
        options('dis', byProvince.map(zone => zone.district), 'Distrito');
        draw();
    }

    let lower = 1;
    $('rangos').innerHTML = LEVELS.map(item => {
        const text = lower.toFixed(2).replace('.', ',') + ' – ' + item.max.toFixed(2).replace('.', ',');
        lower = item.max + 0.01;
        return '<div class="range" style="background:' + COLORS[item.code] + '"><b>' + esc(item.label) + '</b>IPB ' + text + '</div>';
    }).join('');

    options('dep', ZONES.map(zone => zone.department), 'Departamento');
    ['dep', 'pro', 'dis'].forEach(id => $(id).onchange = change);
    window.addEventListener('online', () => selectZone(selectedId));
    window.addEventListener('offline', () => selectZone(selectedId));
    change();
})();
</script>
</body>
</html>
