<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calculadora RECH v5.0 · Moon Group</title>
  <style>
    :root { color-scheme:light; --ink:#17231d; --muted:#627068; --line:#d9e2dc; --brand:#18794e; --soft:#eef7f1; --danger:#a33a32; }
    * { box-sizing:border-box; }
    body { margin:0; background:#f4f7f5; color:var(--ink); font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif; }
    main { width:min(1120px,calc(100% - 32px)); margin:32px auto 64px; }
    h1,h2 { margin:0 0 8px; } h1 { font-size:clamp(25px,4vw,38px); } h2 { font-size:19px; }
    p { margin:0 0 16px; } .muted { color:var(--muted); }
    .grid { display:grid; grid-template-columns:repeat(12,1fr); gap:16px; }
    .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:20px; box-shadow:0 4px 18px rgba(23,35,29,.05); }
    .field { grid-column:span 3; } .field.wide { grid-column:span 6; }
    label { display:block; margin-bottom:6px; font-weight:650; }
    input,select,button { width:100%; min-height:42px; border:1px solid #bbc8c0; border-radius:8px; padding:9px 11px; font:inherit; }
    button { border-color:var(--brand); background:var(--brand); color:#fff; font-weight:700; cursor:pointer; }
    button.secondary { background:#fff; color:var(--brand); } button:disabled { opacity:.55; cursor:not-allowed; }
    .actions { display:flex; align-items:end; gap:10px; grid-column:span 3; }
    .results { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-top:16px; }
    .metric { background:var(--soft); border-radius:10px; padding:14px; }
    .metric strong { display:block; font-size:22px; margin-top:4px; } .metric.final { background:var(--brand); color:white; }
    .status { display:none; margin-top:14px; padding:12px 14px; border-radius:9px; background:var(--soft); }
    .status.error { display:block; color:var(--danger); background:#fff0ee; } .status.ok { display:block; color:#155f40; }
    table { width:100%; border-collapse:collapse; margin-top:12px; } th,td { padding:9px; border-bottom:1px solid var(--line); text-align:left; }
    th { color:var(--muted); font-size:13px; } .hidden { display:none !important; }
    @media (max-width:800px) { .field,.field.wide,.actions { grid-column:span 12; } .results { grid-template-columns:1fr 1fr; } }
  </style>
</head>
<body>
<main>
  <header>
    <h1>Calculadora de emisiones de CO₂</h1>
    <p class="muted">Metodología RECH v5.0 · cálculo automático desde las encuestas KPT guardadas.</p>
  </header>

  <section class="card" id="authCard">
    <h2>Acceso</h2>
    <div class="grid">
      <div class="field wide"><label for="username">Usuario</label><input id="username" autocomplete="username"></div>
      <div class="field wide"><label for="password">Contraseña</label><input id="password" type="password" autocomplete="current-password"></div>
      <div class="actions"><button id="loginButton">Iniciar sesión</button><button id="logoutButton" class="secondary hidden">Cerrar sesión</button></div>
    </div>
    <div id="authStatus" class="status" role="status"></div>
  </section>

  <section class="card" style="margin-top:16px">
    <h2>Fuente de datos</h2>
    <p class="muted">Seleccione el proyecto y el par de encuestas. La API agrupa las participaciones por el identificador único del hogar.</p>
    <div class="grid">
      <div class="field"><label for="projectId">ID del proyecto</label><input id="projectId" type="number" min="1" value="2"></div>
      <div class="actions"><button id="loadButton" class="secondary">Buscar encuestas KPT</button></div>
      <div class="field wide"><label for="baselineSurvey">Línea base</label><select id="baselineSurvey"><option value="">Cargue el proyecto</option></select></div>
      <div class="field wide"><label for="monitoringSurvey">Monitoreo</label><select id="monitoringSurvey"><option value="">Cargue el proyecto</option></select></div>
    </div>
  </section>

  <section class="card" style="margin-top:16px">
    <h2>Parámetros del periodo</h2>
    <div class="grid">
      <div class="field"><label for="monitoringYear">Año de monitoreo</label><input id="monitoringYear" type="number" min="2000" max="2100" value="2026"></div>
      <div class="field"><label for="monitoringMethod">Método</label><select id="monitoringMethod"><option value="MANUAL">Medición manual (0.90)</option><option value="SENSORS">Sensores (1.00)</option></select></div>
      <div class="field"><label for="numberOfStoves">Número de cocinas</label><input id="numberOfStoves" type="number" min="0" step="1" value="3"></div>
      <div class="field"><label for="years">Años acreditados</label><input id="years" type="number" min="0.01" step="0.01" value="1"></div>
      <div class="field wide"><label><input id="destructionEvidence" type="checkbox" style="width:auto;min-height:auto;margin-right:8px">Existe evidencia de destrucción de cocinas sustituidas</label></div>
      <div class="actions"><button id="calculateButton">Calcular reducción</button></div>
    </div>
    <div id="calculationStatus" class="status" role="status"></div>
  </section>

  <section class="card hidden" id="resultCard" style="margin-top:16px">
    <h2>Resultado oficial</h2>
    <div class="results">
      <div class="metric"><span>BEy · Línea base</span><strong id="bey">—</strong><small>tCO₂e</small></div>
      <div class="metric"><span>AEy · Proyecto</span><strong id="aey">—</strong><small>tCO₂e</small></div>
      <div class="metric"><span>Reducción bruta</span><strong id="gross">—</strong><small>tCO₂e</small></div>
      <div class="metric"><span>LEy · Fugas</span><strong id="leakage">—</strong><small>tCO₂e</small></div>
      <div class="metric final"><span>ERy · Reducción neta</span><strong id="ery">—</strong><small>tCO₂e</small></div>
    </div>
    <table>
      <thead><tr><th>Indicador</th><th>Línea base</th><th>Monitoreo</th></tr></thead>
      <tbody>
        <tr><td>Hogares válidos</td><td id="baselineN">—</td><td id="monitoringN">—</td></tr>
        <tr><td>Promedio (kg/hogar/día)</td><td id="baselineMean">—</td><td id="monitoringMean">—</td></tr>
        <tr><td>Ajuste 90/10 (kg/hogar/día)</td><td id="baselineAdjusted">—</td><td id="monitoringAdjusted">—</td></tr>
        <tr><td>Cumple precisión 90/10</td><td id="baselinePrecision">—</td><td id="monitoringPrecision">—</td></tr>
      </tbody>
    </table>
    <p class="muted" id="trace" style="margin-top:14px"></p>
    <ul id="warnings"></ul>
  </section>
</main>

<script>
const apiBase = @json(url('/api'));
const tokenKey = 'moontransparency.calculator_token';
let token = sessionStorage.getItem(tokenKey);
const $ = id => document.getElementById(id);

function status(id, message, type = 'ok') {
  const element = $(id); element.textContent = message; element.className = `status ${type}`;
}
function authHeaders() { return {'Accept':'application/json','Content-Type':'application/json','Authorization':`Bearer ${token}`}; }
function updateAuth() {
  $('loginButton').classList.toggle('hidden', Boolean(token));
  $('logoutButton').classList.toggle('hidden', !token);
  if (token) status('authStatus', 'Sesión activa. Ya puede cargar el proyecto.');
}
async function json(response) {
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const details = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
    throw new Error([payload.message || 'No se pudo completar la solicitud.', details].filter(Boolean).join(' '));
  }
  return payload;
}

$('loginButton').addEventListener('click', async () => {
  try {
    const response = await fetch(`${apiBase}/login`, {method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({username:$('username').value.trim(),password:$('password').value})});
    const payload = await json(response); token = payload.token; sessionStorage.setItem(tokenKey, token); $('password').value = ''; updateAuth();
  } catch (error) { status('authStatus', error.message, 'error'); }
});
$('logoutButton').addEventListener('click', async () => {
  if (token) await fetch(`${apiBase}/logout`, {headers:authHeaders()}).catch(() => {});
  token = null; sessionStorage.removeItem(tokenKey); status('authStatus', 'Sesión cerrada.'); updateAuth();
});
$('loadButton').addEventListener('click', async () => {
  try {
    if (!token) throw new Error('Primero inicie sesión.');
    const payload = await json(await fetch(`${apiBase}/calculator/co2/configuration?project_id=${Number($('projectId').value)}`, {headers:authHeaders()}));
    const surveys = payload.data.surveys;
    fillSurveys('baselineSurvey', surveys.filter(s => s.kind === 'BASELINE'));
    fillSurveys('monitoringSurvey', surveys.filter(s => s.kind === 'MONITORING'));
    const baseline = surveys.find(s => s.id === Number($('baselineSurvey').value));
    if (baseline?.post_survey_id) $('monitoringSurvey').value = String(baseline.post_survey_id);
    const defaults = payload.data.default_parameters;
    $('monitoringYear').value = defaults.monitoring_year; $('monitoringMethod').value = defaults.monitoring_method;
    $('numberOfStoves').value = defaults.number_of_stoves; $('years').value = defaults.years;
    $('destructionEvidence').checked = defaults.destruction_evidence;
    status('calculationStatus', `Se encontraron ${surveys.length} encuestas KPT compatibles.`);
  } catch (error) { status('calculationStatus', error.message, 'error'); }
});
function fillSurveys(id, surveys) {
  const select = $(id); select.replaceChildren();
  for (const survey of surveys) {
    const option = document.createElement('option'); option.value = survey.id;
    option.textContent = `${survey.name} (${survey.participations_count} participaciones)`; select.appendChild(option);
  }
}
$('calculateButton').addEventListener('click', async () => {
  try {
    if (!token) throw new Error('Primero inicie sesión.');
    const body = {project_id:Number($('projectId').value),baseline_survey_id:Number($('baselineSurvey').value),monitoring_survey_id:Number($('monitoringSurvey').value),parameters:{monitoring_year:Number($('monitoringYear').value),monitoring_method:$('monitoringMethod').value,number_of_stoves:Number($('numberOfStoves').value),years:Number($('years').value),destruction_evidence:$('destructionEvidence').checked}};
    const payload = await json(await fetch(`${apiBase}/calculator/co2`, {method:'POST',headers:authHeaders(),body:JSON.stringify(body)}));
    render(payload.data); status('calculationStatus', 'Cálculo RECH completado con los datos guardados.');
  } catch (error) { status('calculationStatus', error.message, 'error'); }
});
function number(value, decimals = 4) { return value === null || value === undefined ? '—' : Number(value).toFixed(decimals); }
function yesNo(value) { return value === null || value === undefined ? '—' : (value ? 'Sí' : 'No'); }
function render(data) {
  const calculation = data.calculation, emissions = calculation.emissions;
  const baseline = calculation.statistics.baseline, monitoring = calculation.statistics.monitoring;
  $('bey').textContent = number(emissions.baseline_final_bey); $('aey').textContent = number(emissions.project_final_aey);
  $('gross').textContent = number(emissions.gross_reduction); $('leakage').textContent = number(emissions.leakage.total_ley); $('ery').textContent = number(emissions.net_reduction_ery);
  $('baselineN').textContent = calculation.status.baseline_sample_size; $('monitoringN').textContent = calculation.status.monitoring_sample_size;
  $('baselineMean').textContent = number(baseline?.mean_kg_household_day,3); $('monitoringMean').textContent = number(monitoring?.mean_kg_household_day,3);
  $('baselineAdjusted').textContent = number(calculation.statistics.baseline_cap?.adjusted_kg_household_day,3); $('monitoringAdjusted').textContent = number(monitoring?.adjusted_kg_household_day,3);
  $('baselinePrecision').textContent = yesNo(baseline?.passes_90_10); $('monitoringPrecision').textContent = yesNo(monitoring?.passes_90_10);
  $('trace').textContent = `${data.source.baseline_survey.name} → ${data.source.monitoring_survey.name}. ${data.source.selected_households} de ${data.source.available_households} hogares seleccionados.`;
  const warnings = $('warnings'); warnings.replaceChildren();
  for (const warning of data.source.warnings) { const item = document.createElement('li'); item.textContent = warning; warnings.appendChild(item); }
  $('resultCard').classList.remove('hidden');
}
updateAuth();
</script>
</body>
</html>
