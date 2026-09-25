<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calculadora de emisiones de CO2 - Reduced Emissions from Cooking and Heating (RECH) v5.0</title>
<style>
  :root{
    --bg:#f6f9fc; --card:#ffffff; --card2:#eef6fd; --accent:#f19049; --accent2:#2182da;
    --text:#17324d; --muted:#60758a; --danger:#d64545; --warn:#d8732d; --border:#c9d8e6;
  }
  *{box-sizing:border-box;}
  body{background:var(--bg);color:var(--text);font-family:'Segoe UI',Arial,sans-serif;margin:0;padding:0;}
  header{background:linear-gradient(100deg,#2182da,#1269b2);padding:18px 24px;border-bottom:4px solid var(--accent);}
  header h1{margin:0;font-size:20px;color:#fff;}
  header p{margin:4px 0 0;color:#eaf5ff;font-size:13px;}
  nav{display:flex;gap:4px;background:#fff;padding:0 16px;flex-wrap:wrap;border-bottom:1px solid var(--border);}
  nav button{background:none;border:none;color:var(--muted);padding:12px 16px;cursor:pointer;font-size:14px;border-bottom:3px solid transparent;}
  nav button.active{color:var(--accent2);border-bottom:3px solid var(--accent);font-weight:700;}
  main{padding:20px;max-width:1100px;margin:0 auto;}
  .tabpanel{display:none;}
  .tabpanel.active{display:block;}
  .card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 2px 8px rgba(33,130,218,.08);}
  .card h3{margin-top:0;color:#176cae;font-size:15px;}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;}
  label{display:block;font-size:12px;color:var(--muted);margin-bottom:3px;}
  input,select{width:100%;background:#fff;border:1px solid var(--border);color:var(--text);border-radius:6px;padding:7px 8px;font-size:13px;}
  input:focus,select:focus{outline:2px solid rgba(33,130,218,.2);border-color:var(--accent2);}
  input.err{border-color:var(--danger);background:#fff1f1;}
  input.survey-locked{background:#edf2f6;color:#506477;cursor:not-allowed;}
  .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
  button.btn{background:var(--accent2);color:#fff;border:none;border-radius:6px;padding:9px 14px;cursor:pointer;font-size:13px;font-weight:600;}
  button.btn.green{background:var(--accent);color:#fff;}
  button.btn.gray{background:#6f8191;}
  button.btn.red{background:var(--danger);}
  button.btn:hover{filter:brightness(.96);}
  table{border-collapse:collapse;width:100%;font-size:12px;margin-top:8px;}
  th,td{border:1px solid var(--border);padding:5px 6px;text-align:center;}
  th{background:#e9f4fc;color:#155f9f;}
  tr:nth-child(even){background:#f7fbfe;}
  .daytable input{width:72px;padding:4px;}
  .composition-table input{min-width:62px;}
  .familyTabs{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px;}
  .familyTabs button{padding:5px 8px;font-size:11px;border-radius:5px;border:1px solid var(--border);background:#fff;color:var(--muted);cursor:pointer;}
  .familyTabs button.active{background:var(--accent2);color:#fff;border-color:var(--accent2);}
  .familyTabs button.hasdata::after{content:' •';color:var(--accent);}
  .configTabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;}
  .configTabs button{padding:8px 11px;font-size:12px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--muted);cursor:pointer;}
  .configTabs button.active{background:var(--accent2);color:#fff;border-color:var(--accent2);font-weight:700;}
  .readonly{background:#edf2f6;color:#506477;cursor:not-allowed;}
  .tablewrap{overflow-x:auto;}
  .resultbox{margin-top:12px;padding:10px;border-radius:7px;background:#eef6fd;border-left:4px solid var(--accent2);}
  .note{font-size:11px;color:#99501f;background:#fff6ef;border:1px solid #f5c7a4;border-radius:6px;padding:8px;margin:8px 0;}
  .badge{display:inline-block;background:#e9f4fc;border-radius:4px;padding:2px 6px;font-size:10px;color:#3e698b;}
  .resnum{font-size:22px;font-weight:700;color:var(--accent2);}
  .subtle{color:var(--muted);font-size:11px;}
  footer{text-align:center;color:var(--muted);font-size:11px;padding:20px;}
  .flexbetween{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
  details summary{cursor:pointer;color:var(--accent2);font-size:13px;margin:6px 0;}
  .data-source{max-width:1100px;margin:14px auto 0;padding:12px 16px;border-radius:8px;border:1px solid var(--border);background:#fff;}
  .data-source.loaded{border-left:5px solid #2b8a3e;}
  .data-source.empty{border-left:5px solid var(--warn);}
  .data-source strong{display:block;margin-bottom:4px;}
  .data-source p{margin:3px 0;font-size:12px;color:var(--muted);}
  .data-source ul{margin:6px 0 0;padding-left:20px;font-size:12px;color:var(--warn);}
</style>
</head>
<body>
<header>
  <h1>Calculadora de Emisiones de CO2 (Reduced Emissions from Cooking and Heating (RECH) v5.0 - Gold Standard)</h1>
</header>
<section class="data-source {{ ($calculatorContext['loaded'] ?? false) ? 'loaded' : 'empty' }}">
  @if ($calculatorContext['loaded'] ?? false)
    <strong>Datos cargados automáticamente desde las encuestas</strong>
    <p>
      Proyecto #{{ $calculatorContext['project_id'] }} ·
      Línea base: {{ $calculatorContext['baseline_survey']['name'] }} ·
      Monitoreo: {{ $calculatorContext['monitoring_survey']['name'] }} ·
      Hogares cargados: {{ $calculatorContext['selected_households'] }}
    </p>
    <p>Los datos KPT están protegidos y solo pueden modificarse desde las encuestas de origen.</p>
    @if (!empty($calculatorContext['warnings']))
      <ul>
        @foreach ($calculatorContext['warnings'] as $warning)
          <li>{{ $warning }}</li>
        @endforeach
      </ul>
    @endif
  @else
    <strong>Calculadora sin datos de encuesta</strong>
    <p>{{ $calculatorContext['message'] ?? 'Solicite un enlace desde el panel administrativo.' }}</p>
  @endif
</section>
<nav id="mainNav"></nav>
<main id="mainContent"></main>

<script>
window.APP_CONFIG = @json($calculatorConfig);
window.CALCULATOR_CONTEXT = @json($calculatorContext);
</script>
<script>
/* ============================================================
   CALCULADORA DE EMISIONES DE CO2 - Reduced Emissions from Cooking and Heating (RECH) v5.0
   ============================================================ */

/* ---------- Estadística: función T inversa (equivalente a TINV de Excel) ---------- */
function logGamma(x){
  const g=7, c=[0.99999999999980993,676.5203681218851,-1259.1392167224028,
    771.32342877765313,-176.61502916214059,12.507343278686905,
    -0.13857109526572012,9.9843695780195716e-6,1.5056327351493116e-7];
  if(x<0.5) return Math.log(Math.PI/Math.sin(Math.PI*x))-logGamma(1-x);
  x-=1; let a=c[0]; const t=x+g+0.5;
  for(let i=1;i<g+2;i++) a+=c[i]/(x+i);
  return 0.5*Math.log(2*Math.PI)+(x+0.5)*Math.log(t)-t+Math.log(a);
}
function betacf(x,a,b){
  const MAXIT=200, EPS=3e-9, FPMIN=1e-30;
  let qab=a+b, qap=a+1, qam=a-1;
  let c=1, d=1-qab*x/qap;
  if(Math.abs(d)<FPMIN) d=FPMIN;
  d=1/d; let h=d;
  for(let m=1;m<=MAXIT;m++){
    const m2=2*m;
    let aa=m*(b-m)*x/((qam+m2)*(a+m2));
    d=1+aa*d; if(Math.abs(d)<FPMIN) d=FPMIN;
    c=1+aa/c; if(Math.abs(c)<FPMIN) c=FPMIN;
    d=1/d; h*=d*c;
    aa=-(a+m)*(qab+m)*x/((a+m2)*(qap+m2));
    d=1+aa*d; if(Math.abs(d)<FPMIN) d=FPMIN;
    c=1+aa/c; if(Math.abs(c)<FPMIN) c=FPMIN;
    d=1/d; const del=d*c; h*=del;
    if(Math.abs(del-1)<EPS) break;
  }
  return h;
}
function betai(a,b,x){
  if(x<=0) return 0; if(x>=1) return 1;
  const bt=Math.exp(logGamma(a+b)-logGamma(a)-logGamma(b)+a*Math.log(x)+b*Math.log(1-x));
  if(x<(a+1)/(a+b+2)) return bt*betacf(x,a,b)/a;
  return 1-bt*betacf(1-x,b,a)/b;
}
function studentCdf(t,df){
  const x=df/(df+t*t);
  const p=0.5*betai(df/2,0.5,x);
  return t>0 ? 1-p : p;
}
// Equivalente a TINV(probabilidad, grados_libertad) de Excel (dos colas)
function TINV(prob, df){
  if(!(df>=1)) return NaN;
  let lo=0, hi=1000;
  for(let i=0;i<200;i++){
    const mid=(lo+hi)/2;
    const p=2*(1-studentCdf(mid,df));
    if(p>prob) lo=mid; else hi=mid;
  }
  return (lo+hi)/2;
}
function mean(arr){ return arr.length? arr.reduce((a,b)=>a+b,0)/arr.length : 0; }
function stdevS(arr){ // STDEV.S (muestral)
  const n=arr.length; if(n<2) return 0;
  const m=mean(arr);
  return Math.sqrt(arr.reduce((a,b)=>a+(b-m)*(b-m),0)/(n-1));
}
// Replica AVERAGEIF(rango,"<>0"): promedio de valores distintos de 0 (comportamiento nativo de Excel)
function avgNonZero(arr){
  const f=arr.filter(v=>typeof v==='number' && !isNaN(v) && v!==0);
  return f.length? mean(f) : 0;
}

/* ============================================================
   CONFIGURACIÓN (ver celda 2 de Colab -> se inyecta en window.APP_CONFIG)
   ============================================================ */
const CFG = window.APP_CONFIG;
const KPT_FIELDS_LOCKED = Boolean(window.CALCULATOR_CONTEXT.loaded);

/* ============================================================
   MODELO DE DATOS
   ============================================================ */
const N_FAM = CFG.maxFamilias;
const DAYS = [0,1,2,3,4,5,6]; // 7 dias KPT

function emptyDay7(){ return [null,null,null,null,null,null,null]; }
function newFamily(i){
  return {
    id:'', // ID del Hogar (obligatorio para que la familia cuente en los promedios)
    lb:{
      comp:{ni:emptyDay7(),mu:emptyDay7(),h1:emptyDay7(),h2:emptyDay7()},
      pesoInicial: null,
      pf: emptyDay7(),
      carbon: emptyDay7()
    },
    mon:{
      comp:{ni:emptyDay7(),mu:emptyDay7(),h1:emptyDay7(),h2:emptyDay7()},
      pesoInicialMej: null,
      pesoInicialTrad: null,
      pfMej: emptyDay7(), carbMej: emptyDay7(),
      pfTrad: emptyDay7(), carbTrad: emptyDay7()
    }
  };
}
const SURVEY_FAMILIES = @json($calculatorFamilies);
const INITIAL_FAMILIES = Array.from({length:N_FAM}, (_,i)=>
  SURVEY_FAMILIES[i]
    ? JSON.parse(JSON.stringify(SURVEY_FAMILIES[i]))
    : newFamily(i)
);
let STATE = {
  config: JSON.parse(JSON.stringify(CFG)),
  families: JSON.parse(JSON.stringify(INITIAL_FAMILIES))
};

/* ============================================================
   PERSISTENCIA LOCAL (localStorage)
   ============================================================ */
const LS_KEY = `co2calc_survey_${window.CALCULATOR_CONTEXT.fingerprint || 'empty'}`;
function saveState(){
  try{ localStorage.setItem(LS_KEY, JSON.stringify(STATE)); }catch(e){ console.warn('No se pudo guardar en localStorage', e); }
}
function loadState(){
  try{
    const raw = localStorage.getItem(LS_KEY);
    if(raw){
      const parsed = JSON.parse(raw);
      if(parsed && parsed.families && parsed.families.length===N_FAM){
        parsed.config = Object.assign({}, CFG, parsed.config||{});
        if(!Array.isArray(parsed.config.nbpyRows)) parsed.config.nbpyRows = JSON.parse(JSON.stringify(CFG.nbpyRows));
        if(!Array.isArray(parsed.config.upyRows)) parsed.config.upyRows = JSON.parse(JSON.stringify(CFG.upyRows));
        if(!Array.isArray(parsed.config.dafRows)) parsed.config.dafRows = JSON.parse(JSON.stringify(CFG.dafRows));
        STATE = {
          config: parsed.config,
          families: KPT_FIELDS_LOCKED
            ? JSON.parse(JSON.stringify(INITIAL_FAMILIES))
            : parsed.families
        };
        return true;
      }
    }
  }catch(e){ console.warn('No se pudo leer localStorage', e); }
  return false;
}

function calculatedNbPy(c){
  return (c.nbpyRows||[]).reduce((sum,row)=>{
    if(!row.operativa) return sum;
    return sum + (Number(row.cantidad)||0)*(Number(row.meses)||0)*(365/12);
  },0);
}
function calculatedUpY(c){
  const rows=c.upyRows||[];
  const total=rows.reduce((s,r)=>s+(Number(r.cantidad)||0),0);
  if(total===0) return 0;
  return rows.reduce((s,r)=>{
    const use=Math.min(Number(r.porcentaje)||0, Number(c.upyCap)||0.75);
    return s+(Number(r.cantidad)||0)*use;
  },0)/total;
}
function calculatedDAF(c){
  const row=(c.dafRows||[]).find(r=>Number(r.year)===Number(c.monitoringYear));
  return row ? Number(row.pct)||0 : Number(c.dafDefault)||0;
}

/* ============================================================
   CALCULOS POR FAMILIA
   Cada familia y cada día utilizan su propia composición familiar.
   ============================================================ */
function familyAEDay(fam, scenario, d){
  const c=fam[scenario].comp;
  return (c.ni[d]||0)*CFG.fChild + (c.mu[d]||0)*CFG.fWoman +
         (c.h1[d]||0)*CFG.fMan1559 + (c.h2[d]||0)*CFG.fMan60plus;
}
function familyAE(fam, scenario){
  return avgNonZero(DAYS.map(d=>familyAEDay(fam,scenario,d)));
}
function familyActive(fam){ return fam.id && fam.id.trim()!==''; }

// Linea base (cocina tradicional). Excel: KPT!I85 = I76-I78-I79 (Peso inicial - sobrante - carbon)
function calcLB(fam){
  const cd = [];
  const cpc = [];
  const aeDays = [];
  for(let d=0; d<7; d++){
    const pf = fam.lb.pf[d], cb = fam.lb.carbon[d]===null? 0 : fam.lb.carbon[d];
    if(pf===null || pf===undefined) continue;
    const c = fam.lb.pesoInicial - pf - cb;
    const ae = familyAEDay(fam,'lb',d);
    cd.push(c);
    aeDays.push(ae);
    cpc.push(ae>0 ? c/ae : 0);
  }
  return {
    AE: avgNonZero(aeDays),
    aeDays,
    cdPromedio: avgNonZero(cd),
    cpcPromedio: avgNonZero(cpc),
    diasConDato: cd.length
  };
}

// Monitoreo (Opción 1 del Excel: cocina tradicional + cocina mejorada simultáneas).
// Excel Familia 1 (fila 128): I128=(I116+I117)-(I119+I120)-(I121+I122)
// *** CORRECCIÓN ***: desde la Familia 2 el Excel original perdía los términos de
// cocina TRADICIONAL (T128=T116-T119-T121). Se generaliza aquí el patrón de la Familia 1.
function calcMon(fam){
  const cd = [];
  const cpc = [];
  const aeDays = [];
  const pInicial = fam.mon.pesoInicialMej + fam.mon.pesoInicialTrad;
  for(let d=0; d<7; d++){
    const pfMej = fam.mon.pfMej[d];
    if(pfMej===null || pfMej===undefined) continue;
    const pfTrad = fam.mon.pfTrad[d]===null? 0 : fam.mon.pfTrad[d];
    const cbMej = fam.mon.carbMej[d]===null? 0 : fam.mon.carbMej[d];
    const cbTrad = fam.mon.carbTrad[d]===null? 0 : fam.mon.carbTrad[d];
    const c = pInicial - (pfMej+pfTrad) - (cbMej+cbTrad);
    const ae = familyAEDay(fam,'mon',d);
    cd.push(c);
    aeDays.push(ae);
    cpc.push(ae>0 ? c/ae : 0);
  }
  return {
    AE: avgNonZero(aeDays),
    aeDays,
    cdPromedio: avgNonZero(cd),
    cpcPromedio: avgNonZero(cpc),
    diasConDato: cd.length
  };
}

/* ============================================================
   CALCULOS AGREGADOS (estadística 90/10, TOPES, CALCULO final)
   ============================================================ */
function runFullCalculation(){
  const active = STATE.families.filter(familyActive);
  const perFamLB = active.map(calcLB);
  const perFamMon = active.map(calcMon);

  /* ---- BE-PRECISION EST. (90-10): Pb,stat / Pb,adj ---- */
  const cdListLB = perFamLB.map(f=>f.cdPromedio);       // BE-PRECISION!D6:D25
  const cpcListLB = perFamLB.map(f=>f.cpcPromedio);     // BE-PRECISION!E6:E25
  const aeListLB = perFamLB.map(f=>f.AE);               // BE-PRECISION!C6:C25 ("Personas x familia")
  const H6 = mean(cdListLB.filter(v=>!isNaN(v)));        // PROMEDIO (kg/hogar/dia)
  const H7 = stdevS(cdListLB);                           // DESVIACION ESTANDAR
  const H8 = cdListLB.length;                            // muestra
  // Nota: la prueba de precision 90/10 requiere muestra>=2 (grados de libertad>=1).
  // Con muestra<2 no es posible calcular el margen de error: se usa Pb,stat = Pb,mean.
  const H13 = H8>=2 ? TINV(0.2, H8-1) : NaN;               // Valor critico T (90%, 1 cola)
  const H14 = H8>=2 ? H7/Math.sqrt(H8) : 0;                // Error estandar
  const H15 = H8>=2 ? H14*H13 : 0;                         // Margen de error absoluto
  const H18 = (H8>=2 && H6!==0) ? H15/H6 : 0;              // % precision (margen relativo)
  const H21 = (H8<2 || H18<=0.10) ? H6 : (H6-H15);          // Pb,stat (kg/hogar/dia)

  /* ---- TOPES ---- */
  const C8 = avgNonZero(cpcListLB) * 365/1000;             // KPT anual per capita (t/año/persona)
  const C9 = Math.min(C8, CFG.pcapTope);                    // Pcap final (tope 1.25 t/año/persona)
  const C10 = avgNonZero(aeListLB);                         // tamaño promedio familiar (AE) de la muestra
  const C12 = C9*(C10/365);                                 // tope diario por hogar (t/hogar/dia)
  const C13 = C12*1000;                                     // tope diario por hogar (kg/hogar/dia)
  const C23 = Math.min(H21, C13);                            // Pb,adj (kg/hogar/dia) final tras aplicar tope
  const C24 = C23/1000;                                      // Pb,adj (t/hogar/dia)

  /* ---- AE-PREC. EST. (90-10) - Pp,adj ---- */
  const cdListMon = perFamMon.map(f=>f.cdPromedio);
  const H11 = mean(cdListMon.filter(v=>!isNaN(v)));
  const H12 = stdevS(cdListMon);
  const H13b = cdListMon.length;
  const H18b = H13b>=2 ? TINV(0.2, H13b-1) : NaN;
  const H19 = H13b>=2 ? H12/Math.sqrt(H13b) : 0;
  const H20 = H13b>=2 ? H19*H18b : 0;
  const H23 = (H13b>=2 && H11!==0) ? H20/H11 : 0;
  const H26 = (H13b<2 || H23<=0.10) ? H11 : (H11+H20);        // Pp,adj (kg/hogar/dia) -> se SUMA el margen (criterio conservador)
  const H27 = H26/1000;                                      // Pp,adj (t/hogar/dia)

  /* ---- CALCULO: parametros ---- */
  const D5 = calculatedNbPy(STATE.config); // dias/año, calculado desde su matriz
  const D6 = calculatedUpY(STATE.config);  // tasa ponderada, calculada desde su matriz
  const D7 = H6/1000;                // Pb,mean t/dia
  const D8 = C24;                    // Pb,adj t/dia
  const D9 = H27;                    // Pp,adj t/dia
  const D10 = STATE.config.NCVbfuel;
  const D11 = STATE.config.EFbfCO2;
  const D12 = STATE.config.fNRBby;
  const D13 = STATE.config.EFbfNonCO2;
  const nYears = STATE.config.nYears;

  const annualBaselineSinAjuste = D5*D6*D7*D10*(D11*D12+D13);  // CALCULO!O17 (informativo)
  const O37 = annualBaselineSinAjuste*nYears;                   // CALCULO!O37 (informativo)

  const annualBaselineAjustado = D5*D6*D8*D10*(D11*D12+D13);    // CALCULO!O43
  const O63 = annualBaselineAjustado*nYears;                    // CALCULO!O63

  const dafPct = calculatedDAF(STATE.config);
  const G12 = O63*(1-dafPct);                                   // Ajuste DAF!G12
  const D60 = Math.min(O63, G12);                                // BEy candidato
  const BEy = D60;                                                // CALCULO!F95

  const annualProject = D5*D6*D9*D10*(D11*D12+D13);              // CALCULO!O70
  const O90 = annualProject*nYears;                               // CALCULO!O90
  const AEy = O90;                                                 // CALCULO!F96

  const reduccionBruta = BEy - AEy;                                // CALCULO!F97

  /* ---- Emisiones x fuga LEy ---- */
  const LEEmbodied = STATE.config.numCocinas*STATE.config.emisionFabCocina/STATE.config.aniosVidaCocina; // F14
  const LEMarket = STATE.config.evidenciaDestruccion ? 0 : (BEy-AEy)*STATE.config.leMarketPct;             // F30
  const LEy = LEEmbodied+LEMarket;                                                                          // F34

  /* ---- Calculo final ---- */
  const HEind = STATE.config.HEind;
  const ERy = (reduccionBruta*HEind)-LEy;                          // CALCULO!E125

  return {
    perFamLB, perFamMon, active,
    H6,H7,H8,H13,H14,H15,H18,H21,
    C8,C9,C10,C12,C13,C23,C24,
    H11,H12,H13b,H18b,H19,H20,H23,H26,H27,
    D5,D6,D7,D8,D9,D10,D11,D12,D13,nYears,
    O37, annualBaselineAjustado, O63, dafPct, G12, D60, BEy,
    annualProject, O90, AEy, reduccionBruta,
    LEEmbodied, LEMarket, LEy, HEind, ERy
  };
}

/* ============================================================
   VALIDACION
   ============================================================ */
function validateAll(){
  const errors = [];
  STATE.families.forEach((fam, idx)=>{
    if(!familyActive(fam)) return;
    const label = `Familia ${idx+1} (${fam.id})`;
    ['lb','mon'].forEach(sc=>DAYS.forEach(d=>['ni','mu','h1','h2'].forEach(k=>{
      const v=fam[sc].comp[k][d];
      if(v!==null && v<0) errors.push(`${label}: composición ${sc}, día ${d+1}, "${k}" no puede ser negativa.`);
    })));
    if(fam.lb.pesoInicial<0) errors.push(`${label}: peso inicial LB negativo.`);
    if(fam.mon.pesoInicialMej<0 || fam.mon.pesoInicialTrad<0) errors.push(`${label}: peso inicial Monitoreo negativo.`);
    DAYS.forEach(d=>{
      if(fam.lb.pf[d]!==null && fam.lb.pf[d]<0) errors.push(`${label}: LB día ${d+1} peso final negativo.`);
      if(fam.lb.carbon[d]!==null && fam.lb.carbon[d]<0) errors.push(`${label}: LB día ${d+1} carbón negativo.`);
      if(fam.mon.pfMej[d]!==null && fam.mon.pfMej[d]<0) errors.push(`${label}: Monitoreo día ${d+1} sobrante mejorada negativo.`);
      if(fam.mon.pfTrad[d]!==null && fam.mon.pfTrad[d]<0) errors.push(`${label}: Monitoreo día ${d+1} sobrante tradicional negativo.`);
    });
  });
  return errors;
}

function clearAll(){
  if(KPT_FIELDS_LOCKED) return;
  STATE.families = Array.from({length:N_FAM}, (_,i)=>newFamily(i));
}

/* ============================================================
   UI
   ============================================================ */
let currentTab = 'config';
let currentFamily = 0;
let currentConfigTab = 'defaults';
let lastResults = null;

const TABS = [
  {id:'config', label:'⚙️ Configuración'},
  {id:'lb', label:'🔥Línea Base'},
  {id:'mon', label:'🍳Monitoreo'},
  {id:'res', label:'📊Resultados'}
];

function el(tag, attrs, ...children){
  const e = document.createElement(tag);
  if(attrs) for(const k in attrs){
    if(k==='class') e.className = attrs[k];
    else if(k.startsWith('on')) e.addEventListener(k.slice(2), attrs[k]);
    else e.setAttribute(k, attrs[k]);
  }
  children.flat().forEach(c=>{
    if(c===null||c===undefined) return;
    e.appendChild(typeof c==='string' ? document.createTextNode(c) : c);
  });
  return e;
}
function fmt(n, dec){ if(n===null||n===undefined||isNaN(n)) return '—'; return Number(n).toFixed(dec===undefined?4:dec); }

function renderNav(){
  const nav = document.getElementById('mainNav');
  nav.innerHTML='';
  TABS.forEach(t=>{
    nav.appendChild(el('button',{class:t.id===currentTab?'active':'', onclick:()=>{currentTab=t.id; renderAll();}}, t.label));
  });
}

function numInput(value, onChange, opts){
  opts = opts||{};
  const inp = el('input',{type:'number', step:opts.step||'any', placeholder:opts.placeholder||''});
  inp.value = (value===null||value===undefined) ? '' : value;
  if(opts.readonly){
    inp.readOnly = true;
    inp.classList.add('survey-locked');
    inp.title = 'Este dato proviene de una encuesta y no puede editarse aquí.';
  }
  inp.addEventListener('input', ()=>{
    const v = inp.value==='' ? (opts.allowEmpty? null : 0) : parseFloat(inp.value);
    if(v!==null && v<0) inp.classList.add('err'); else inp.classList.remove('err');
    onChange(v);
    saveState();
  });
  return inp;
}
function textInput(value, onChange, opts){
  opts = opts||{};
  const inp = el('input',{type:'text'});
  inp.value = value||'';
  if(opts.readonly){
    inp.readOnly = true;
    inp.classList.add('survey-locked');
    inp.title = 'Este dato proviene de una encuesta y no puede editarse aquí.';
  }
  inp.addEventListener('input', ()=>{ onChange(inp.value); saveState(); });
  return inp;
}

/* ---------- CONFIGURACION TAB ---------- */
const CONFIG_TABS = [
  {id:'defaults', label:'Valores predeterminados'},
  {id:'nbpy', label:'Nb,p,y'},
  {id:'upy', label:'Up,y'},
  {id:'daf', label:'Ajuste DAF'},
  {id:'leak', label:'Fugas LEy'},
  {id:'hawthorne', label:'HEind'},
  {id:'actions', label:'Acciones'}
];

function readonlyField(label,value,unit){
  const wrap=el('div',{});
  wrap.appendChild(el('label',{},label+(unit?` (${unit})`:'')));
  const inp=numInput(value,()=>{},{});
  inp.disabled=true; inp.classList.add('readonly');
  wrap.appendChild(inp);
  return wrap;
}

function renderConfigTabs(container){
  const tabs=el('div',{class:'configTabs'});
  CONFIG_TABS.forEach(t=>tabs.appendChild(el('button',{
    class:t.id===currentConfigTab?'active':'',
    onclick:()=>{currentConfigTab=t.id; renderAll();}
  },t.label)));
  container.appendChild(tabs);
}

function renderDefaults(container,c){
  function editableField(label,key,unit,step){
    const wrap=el('div',{});
    wrap.appendChild(el('label',{},label+(unit?` (${unit})`:'')));
    wrap.appendChild(numInput(c[key],v=>{c[key]=v;},{step:step||'any'}));
    return wrap;
  }

  const familyCard=el('div',{class:'card'});
  familyCard.appendChild(el('h3',{},'Factores por integrante de la familia'));
  familyCard.appendChild(el('p',{class:'subtle'},'Factores de Adulto Equivalente utilizados para calcular la composición familiar diaria. Se cargan con los valores del Excel, pero pueden editarse.'));
  const familyGrid=el('div',{class:'grid'});
  [
    ['Niño de 0 a 14 años','fChild','factor','0.05'],
    ['Mujer mayor de 14 años','fWoman','factor','0.05'],
    ['Hombre de 15 a 59 años','fMan1559','factor','0.05'],
    ['Hombre mayor de 59 años','fMan60plus','factor','0.05']
  ].forEach(x=>familyGrid.appendChild(editableField(...x)));
  familyCard.appendChild(familyGrid);
  container.appendChild(familyCard);

  const card=el('div',{class:'card'});
  card.appendChild(el('h3',{},'Otros valores predeterminados'));
  card.appendChild(el('p',{class:'subtle'},'Valores iniciales tomados del Excel corregido y del estándar. También pueden modificarse cuando exista un sustento técnico para hacerlo.'));
  const grid=el('div',{class:'grid'});
  [
    ['Pcap máximo','pcapTope','t/año/persona','0.01'],
    ['NCVb,fuel','NCVbfuel','TJ/t','0.0001'],
    ['EFb,f,CO2','EFbfCO2','tCO2/TJ','0.01'],
    ['fNRB','fNRBby','fracción','0.01'],
    ['EFb,f,non-CO2','EFbfNonCO2','tCO2e/TJ','0.01'],
    ['Años evaluados','nYears','año','1']
  ].forEach(x=>grid.appendChild(editableField(...x)));
  card.appendChild(grid); container.appendChild(card);
}

function renderNbPy(container,c){
  const card=el('div',{class:'card'});
  card.appendChild(el('h3',{},'Nb,p,y — días acumulados de cocinas operativas'));
  card.appendChild(el('p',{class:'subtle'},"Replica la matriz de 'Nb,p,y 2'. Fórmula por fila: cantidad × meses operativos × 365/12."));
  const tw=el('div',{class:'tablewrap'}), table=el('table',{});
  table.appendChild(el('tr',{},el('th',{},'Ítem'),el('th',{},'ID del hogar'),el('th',{},'Cantidad'),el('th',{},'Operativa'),el('th',{},'Fecha de inicio'),el('th',{},'Meses operativos')));
  c.nbpyRows.forEach((row,i)=>{
    const tr=el('tr',{},el('td',{},String(i+1)));
    let td=el('td',{}); td.appendChild(textInput(row.id,v=>row.id=v)); tr.appendChild(td);
    td=el('td',{}); td.appendChild(numInput(row.cantidad,v=>row.cantidad=v,{step:'1'})); tr.appendChild(td);
    td=el('td',{}); const chk=el('input',{type:'checkbox'}); chk.checked=!!row.operativa; chk.addEventListener('change',()=>{row.operativa=chk.checked;saveState();renderAll();}); td.appendChild(chk); tr.appendChild(td);
    td=el('td',{}); const dt=el('input',{type:'date'}); dt.value=row.fechaInicio||''; dt.addEventListener('input',()=>{row.fechaInicio=dt.value;saveState();}); td.appendChild(dt); tr.appendChild(td);
    td=el('td',{}); td.appendChild(numInput(row.meses,v=>{row.meses=Math.min(12,v||0);},{step:'0.1'})); tr.appendChild(td);
    table.appendChild(tr);
  });
  tw.appendChild(table); card.appendChild(tw);
  card.appendChild(el('div',{class:'resultbox'},`Nb,p,y calculado: ${fmt(calculatedNbPy(c),2)} días/año`));
  container.appendChild(card);
}

function renderUpY(container,c){
  const card=el('div',{class:'card'});
  card.appendChild(el('h3',{},'Up,y — tasa de uso ponderada'));
  card.appendChild(el('p',{class:'subtle'},"Replica la matriz de 'Upy 2'. Cada porcentaje se limita al máximo obligatorio de 75% antes de ponderarlo."));
  const table=el('table',{});
  table.appendChild(el('tr',{},el('th',{},'Ítem'),el('th',{},'Rango de edad (años)'),el('th',{},'Cantidad de cocinas'),el('th',{},'Uso observado'),el('th',{},'Uso aplicable (máx. 75%)'),el('th',{},'Valor ponderado')));
  c.upyRows.forEach((row,i)=>{
    const applicable=Math.min(Number(row.porcentaje)||0,Number(c.upyCap)||0.75);
    const tr=el('tr',{},el('td',{},String(i+1)));
    let td=el('td',{}); td.appendChild(textInput(row.rango,v=>row.rango=v)); tr.appendChild(td);
    td=el('td',{}); td.appendChild(numInput(row.cantidad,v=>row.cantidad=v,{step:'1'})); tr.appendChild(td);
    td=el('td',{}); td.appendChild(numInput(row.porcentaje,v=>{row.porcentaje=Math.min(1,v||0);},{step:'0.01'})); tr.appendChild(td);
    tr.appendChild(el('td',{},fmt(applicable,2)));
    tr.appendChild(el('td',{},fmt((Number(row.cantidad)||0)*applicable,2)));
    table.appendChild(tr);
  });
  card.appendChild(table);
  card.appendChild(el('div',{class:'resultbox'},`Up,y calculado: ${fmt(calculatedUpY(c),4)} (${fmt(calculatedUpY(c)*100,2)}%)`));
  container.appendChild(card);
}

function renderDAF(container,c){
  const card=el('div',{class:'card'});
  card.appendChild(el('h3',{},'Ajuste DAF por año de monitoreo'));
  const table=el('table',{});
  table.appendChild(el('tr',{},el('th',{},'Año'),el('th',{},'Descuento DAF')));
  c.dafRows.forEach(row=>{
    const tr=el('tr',{}); let td=el('td',{}); td.appendChild(numInput(row.year,v=>row.year=v,{step:'1'})); tr.appendChild(td);
    td=el('td',{}); td.appendChild(numInput(row.pct,v=>{row.pct=Math.min(1,v||0);},{step:'0.01'})); tr.appendChild(td); table.appendChild(tr);
  });
  card.appendChild(table);
  const row=el('div',{class:'row',style:'margin-top:12px;'});
  row.appendChild(el('label',{style:'min-width:180px;'},'Año de monitoreo aplicado:'));
  const sel=el('select',{});
  c.dafRows.forEach(r=>{const o=el('option',{value:r.year},String(r.year));if(Number(r.year)===Number(c.monitoringYear))o.selected=true;sel.appendChild(o);});
  sel.addEventListener('change',()=>{c.monitoringYear=Number(sel.value);saveState();renderAll();}); row.appendChild(sel); card.appendChild(row);
  card.appendChild(el('div',{class:'resultbox'},`DAF aplicado: ${fmt(calculatedDAF(c)*100,2)}%`)); container.appendChild(card);
}

function renderLeak(container,c){
  const card=el('div',{class:'card'}); card.appendChild(el('h3',{},'Fugas del proyecto — LEy'));
  const grid=el('div',{class:'grid'});
  const w=el('div',{}); w.appendChild(el('label',{},'Número de cocinas mejoradas')); w.appendChild(numInput(c.numCocinas,v=>c.numCocinas=v,{step:'1'})); grid.appendChild(w);
  grid.appendChild(readonlyField('Emisión de fabricación por cocina',c.emisionFabCocina,'tCO2e/cocina'));
  grid.appendChild(readonlyField('Vida útil de la cocina',c.aniosVidaCocina,'años'));
  grid.appendChild(readonlyField('LEMarket conservador',c.leMarketPct,'fracción'));
  card.appendChild(grid);
  const ev=el('div',{class:'row',style:'margin-top:12px;'}); const chk=el('input',{type:'checkbox'}); chk.checked=!!c.evidenciaDestruccion; chk.addEventListener('change',()=>{c.evidenciaDestruccion=chk.checked;saveState();}); ev.appendChild(chk); ev.appendChild(el('label',{},'Existe evidencia física de destrucción de las cocinas tradicionales (LEMarket = 0)')); card.appendChild(ev);
  card.appendChild(el('p',{class:'subtle'},'LEEmbodied = número de cocinas × 0.0017 / 5. LEMarket aplica 2% de la reducción bruta cuando no existe evidencia.'));
  container.appendChild(card);
}

function renderHawthorne(container,c){
  const card=el('div',{class:'card'}); card.appendChild(el('h3',{},'HEind — ajuste por efecto Hawthorne'));
  const sel=el('select',{});
  [[0.9,'Monitoreo manual mediante encuestas (0.90)'],[1.0,'Monitoreo digital continuo con sensores (1.00)']].forEach(([v,l])=>{const o=el('option',{value:v},l);if(Number(c.HEind)===v)o.selected=true;sel.appendChild(o);});
  sel.addEventListener('change',()=>{c.HEind=Number(sel.value);saveState();}); card.appendChild(sel);
  card.appendChild(el('p',{class:'subtle'},'El Excel corregido utiliza 0.90 porque el proyecto se encuentra en la opción de monitoreo manual.'));
  container.appendChild(card);
}

function renderActions(container){
  const card=el('div',{class:'card'}); card.appendChild(el('h3',{},'Acciones')); const row=el('div',{class:'row'});
  if(!KPT_FIELDS_LOCKED){
    row.appendChild(el('button',{class:'btn red',onclick:()=>{if(confirm('¿Borrar todos los datos ingresados?')){clearAll();saveState();renderAll();}}},'🗑️ Limpiar datos de familias'));
  }
  row.appendChild(el('button',{class:'btn',onclick:()=>{currentTab='res';runAndRender();}},'▶️ Calcular')); card.appendChild(row); container.appendChild(card);
}

function renderConfig(container){
  const c=STATE.config; renderConfigTabs(container);
  if(currentConfigTab==='defaults') renderDefaults(container,c);
  else if(currentConfigTab==='nbpy') renderNbPy(container,c);
  else if(currentConfigTab==='upy') renderUpY(container,c);
  else if(currentConfigTab==='daf') renderDAF(container,c);
  else if(currentConfigTab==='leak') renderLeak(container,c);
  else if(currentConfigTab==='hawthorne') renderHawthorne(container,c);
  else renderActions(container);
}

/* ---------- FAMILY TABS (selector) ---------- */
function renderFamilyTabs(container, scenario){
  const wrap = el('div',{class:'familyTabs'});
  STATE.families.forEach((fam,i)=>{
    const has = familyActive(fam);
    wrap.appendChild(el('button',{
      class:(i===currentFamily?'active ':'')+(has?'hasdata':''),
      onclick:()=>{ currentFamily=i; renderAll(); }
    }, fam.id ? fam.id : `F${i+1}`));
  });
  container.appendChild(wrap);
}

function renderCompositionCard(fam, scenario){
  const card = el('div',{class:'card'});
  const nombre = scenario==='lb' ? 'Línea base' : 'Monitoreo';
  card.appendChild(el('h3',{}, `Hogar y composición familiar diaria — ${nombre} — Familia ${currentFamily+1}`));
  const idw = el('div',{});
  idw.appendChild(el('label',{}, 'ID del Hogar (obligatorio para incluir esta familia en los promedios)'));
  idw.appendChild(textInput(fam.id, v=>{fam.id=v;}, {readonly:KPT_FIELDS_LOCKED}));
  card.appendChild(idw);

  const comp = fam[scenario].comp;
  const table = el('table',{class:'daytable composition-table'});
  table.appendChild(el('tr',{},
    el('th',{},'Día'),
    el('th',{},'Niños (0–14 años)'),
    el('th',{},'Mujeres (>14 años)'),
    el('th',{},'Hombres (15–59 años)'),
    el('th',{},'Hombres (>59 años)')
  ));
  for(let d=0; d<7; d++){
    const tr=el('tr',{},el('td',{},String(d+1)));
    ['ni','mu','h1','h2'].forEach(key=>{
      const td=el('td',{});
      td.appendChild(numInput(comp[key][d],v=>{comp[key][d]=v;},{allowEmpty:true,step:'1',readonly:KPT_FIELDS_LOCKED}));
      tr.appendChild(td);
    });
    table.appendChild(tr);
  }
  card.appendChild(table);
  card.appendChild(el('p',{class:'subtle'}, `Adultos Equivalentes promedio: ${fmt(familyAE(fam,scenario),2)}. Cada día se calcula con las personas registradas para ese día.`));
  return card;
}

function dayTable(title, cols, unitNote){
  // cols: [{label, values:[7], onChange(d,v), allowEmpty}]
  const card = el('div',{class:'card'});
  card.appendChild(el('h3',{}, title));
  if(unitNote) card.appendChild(el('p',{class:'subtle'}, unitNote));
  const table = el('table',{class:'daytable'});
  const head = el('tr',{}, el('th',{},'Día'));
  cols.forEach(c=>head.appendChild(el('th',{}, c.label)));
  table.appendChild(head);
  for(let d=0; d<7; d++){
    const tr = el('tr',{}, el('td',{}, String(d+1)));
    cols.forEach(c=>{
      const td = el('td',{});
      td.appendChild(numInput(c.values[d], v=>c.onChange(d,v), {allowEmpty:true,readonly:KPT_FIELDS_LOCKED}));
      tr.appendChild(td);
    });
    table.appendChild(tr);
  }
  card.appendChild(table);
  return card;
}

function renderLB(container){
  renderFamilyTabs(container,'lb');
  const fam = STATE.families[currentFamily];
  container.appendChild(renderCompositionCard(fam,'lb'));

  const card = el('div',{class:'card'});
  card.appendChild(el('h3',{}, 'Línea Base — Cocina tradicional (KPT!F59:HP98)'));
  const w = el('div',{});
  w.appendChild(el('label',{}, 'Peso inicial de leña (kg) — KPT!I76, igual cada día del protocolo'));
  w.appendChild(numInput(fam.lb.pesoInicial, v=>{fam.lb.pesoInicial=v||0;}, {readonly:KPT_FIELDS_LOCKED}));
  card.appendChild(w);
  container.appendChild(card);

  container.appendChild(dayTable('KPT 7 días — Línea Base', [
    {label:'Peso final / sobrante (kg)', values:fam.lb.pf, onChange:(d,v)=>fam.lb.pf[d]=v},
    {label:'Carbón producido (kg)', values:fam.lb.carbon, onChange:(d,v)=>fam.lb.carbon[d]=v}
  ], 'KPT!I78 (sobrante) e I79 (carbón). Consumo diario Cd = Peso inicial − sobrante − carbón (KPT!I85).'));

  const res = calcLB(fam);
  const rescard = el('div',{class:'card'});
  rescard.appendChild(el('h3',{},'Resultados intermedios (esta familia)'));
  rescard.appendChild(el('p',{}, `Días con dato: ${res.diasConDato}/7  ·  Consumo diario promedio (Cd): ${fmt(res.cdPromedio,3)} kg/hogar/día  ·  Consumo per cápita (Cpc): ${fmt(res.cpcPromedio,3)} kg/persona/día`));
  container.appendChild(rescard);
}

function renderMon(container){
  renderFamilyTabs(container,'mon');
  const fam = STATE.families[currentFamily];
  container.appendChild(renderCompositionCard(fam,'mon'));

  const card = el('div',{class:'card'});
  card.appendChild(el('h3',{}, 'Monitoreo — Opción 1: Cocina tradicional + Cocina mejorada (KPT!F99:HP138)'));
  card.appendChild(el('p',{class:'subtle'}, 'El Excel original define 2 opciones de monitoreo; se usa la Opción 1 (mixta) porque es la que alimenta a Pp,adj en la hoja "AE-PREC…". La Opción 2 (solo cocina mejorada) no se referenciaba en ningún resultado final del archivo, por lo que no se incluye.'));
  const grid = el('div',{class:'grid'});
  function pw(label,key){
    const w = el('div',{});
    w.appendChild(el('label',{}, label));
    w.appendChild(numInput(fam.mon[key], v=>{fam.mon[key]=v||0;}, {readonly:KPT_FIELDS_LOCKED}));
    grid.appendChild(w);
  }
  pw('Peso inicial cocina mejorada (kg) — KPT!I116', 'pesoInicialMej');
  pw('Peso inicial cocina tradicional (kg) — KPT!I117', 'pesoInicialTrad');
  card.appendChild(grid);
  container.appendChild(card);

  container.appendChild(dayTable('KPT 7 días — Cocina mejorada', [
    {label:'Sobrante mejorada (kg)', values:fam.mon.pfMej, onChange:(d,v)=>fam.mon.pfMej[d]=v},
    {label:'Carbón mejorada (kg)', values:fam.mon.carbMej, onChange:(d,v)=>fam.mon.carbMej[d]=v}
  ], 'KPT!I119 (sobrante mejorada), I121 (carbón mejorada).'));

  container.appendChild(dayTable('KPT 7 días — Cocina tradicional (uso simultáneo, opcional)', [
    {label:'Sobrante tradicional (kg)', values:fam.mon.pfTrad, onChange:(d,v)=>fam.mon.pfTrad[d]=v},
    {label:'Carbón tradicional (kg)', values:fam.mon.carbTrad, onChange:(d,v)=>fam.mon.carbTrad[d]=v}
  ], "KPT!I120 (sobrante tradicional), I122 (carbón tradicional). Déjelo vacío/0 si la familia ya no usa la cocina tradicional."));

  const res = calcMon(fam);
  const rescard = el('div',{class:'card'});
  rescard.appendChild(el('h3',{},'Resultados intermedios (esta familia)'));
  rescard.appendChild(el('p',{}, `Días con dato: ${res.diasConDato}/7  ·  Consumo diario promedio (Cd): ${fmt(res.cdPromedio,3)} kg/hogar/día  ·  Consumo per cápita (Cpc): ${fmt(res.cpcPromedio,3)} kg/persona/día`));
  container.appendChild(rescard);
}

/* ---------- RESULTADOS TAB ---------- */
function runAndRender(){
  const errs = validateAll();
  if(errs.length){
    alert('Hay valores inválidos (negativos). Corrija:\n\n'+errs.slice(0,8).join('\n'));
  }
  lastResults = runFullCalculation();
  renderAll();
}

function kv(label, value, unit){
  return el('tr',{}, el('td',{style:'text-align:left;'}, label), el('td',{}, value), el('td',{class:'subtle'}, unit||''));
}

function renderRes(container){
  if(!lastResults) lastResults = runFullCalculation();
  const r = lastResults;

  // Cada escenario puede consultarse de forma independiente. La reducción neta
  // solo es válida cuando existen datos KPT tanto de línea base como de monitoreo.
  const tieneLineaBase = r.perFamLB.some(f=>f.diasConDato>0);
  const tieneMonitoreo = r.perFamMon.some(f=>f.diasConDato>0);

  const summary = el('div',{class:'card'});
  summary.appendChild(el('h3',{},'Resultado final'));
  const resumen = el('table',{});
  resumen.appendChild(kv('Emisiones de Línea Base (BEy)', tieneLineaBase ? fmt(r.BEy,4) : 'Pendiente de datos', tieneLineaBase ? 't CO2e' : ''));
  resumen.appendChild(kv('Emisiones del Proyecto (AEy)', tieneMonitoreo ? fmt(r.AEy,4) : 'Pendiente de datos', tieneMonitoreo ? 't CO2e' : ''));
  summary.appendChild(resumen);
  summary.appendChild(el('h3',{},'Reducción Neta de Emisiones (ERy)'));
  if(tieneLineaBase && tieneMonitoreo){
    summary.appendChild(el('div',{class:'resnum'}, fmt(r.ERy,4)+' t CO2e'));
    summary.appendChild(el('p',{class:'subtle'}, 'Resultado final — CALCULO!E125 = ((BEy−AEy)×HEind) − LEy'));
  }else{
    summary.appendChild(el('div',{class:'resnum'}, 'Pendiente'));
    summary.appendChild(el('p',{class:'subtle'}, 'Complete línea base y monitoreo para calcular la reducción neta. Las emisiones del escenario disponible se muestran arriba como referencia.'));
  }
  container.appendChild(summary);

  const t1 = el('div',{class:'card'});
  t1.appendChild(el('h3',{},'1) Familias y estadística — Línea Base (BE-PRECISION EST. 90-10)'));
  let tbl = el('table',{});
  tbl.appendChild(kv('Familias activas (muestra)', r.H8));
  tbl.appendChild(kv('Promedio Cd (Pb,mean)', fmt(r.H6,3), 'kg/hogar/día'));
  tbl.appendChild(kv('Desviación estándar', fmt(r.H7,3)));
  tbl.appendChild(kv('T crítico (90%, 1 cola)', fmt(r.H13,4)));
  tbl.appendChild(kv('Error estándar', fmt(r.H14,4)));
  tbl.appendChild(kv('Margen de error absoluto', fmt(r.H15,4)));
  tbl.appendChild(kv('% Precisión (margen relativo)', fmt(r.H18*100,2), '%'));
  tbl.appendChild(kv('Pb,stat', fmt(r.H21,3), 'kg/hogar/día'));
  t1.appendChild(tbl);
  container.appendChild(t1);

  const t2 = el('div',{class:'card'});
  t2.appendChild(el('h3',{},'2) Topes per cápita (TOPES)'));
  tbl = el('table',{});
  tbl.appendChild(kv('Consumo per cápita anual (KPT)', fmt(r.C8,3), 't/año/persona'));
  tbl.appendChild(kv('Pcap final (tras aplicar tope)', fmt(r.C9,3), 't/año/persona'));
  tbl.appendChild(kv('Tamaño familiar promedio (AE)', fmt(r.C10,2)));
  tbl.appendChild(kv('Tope diario por hogar', fmt(r.C13,3), 'kg/hogar/día'));
  tbl.appendChild(kv('Pb,adj', fmt(r.C24,5), 't/hogar/día'));
  t2.appendChild(tbl);
  container.appendChild(t2);

  const t3 = el('div',{class:'card'});
  t3.appendChild(el('h3',{},'3) Familias y estadística — Monitoreo (AE-PREC EST. 90-10, Pp,adj)'));
  tbl = el('table',{});
  tbl.appendChild(kv('Familias con dato en monitoreo', r.H13b));
  tbl.appendChild(kv('Promedio Cd', fmt(r.H11,3), 'kg/hogar/día'));
  tbl.appendChild(kv('Desviación estándar', fmt(r.H12,3)));
  tbl.appendChild(kv('% Precisión', fmt(r.H23*100,2), '%'));
  tbl.appendChild(kv('Pp,adj', fmt(r.H27,5), 't/hogar/día'));
  t3.appendChild(tbl);
  container.appendChild(t3);

  const t4 = el('div',{class:'card'});
  t4.appendChild(el('h3',{},'4) CALCULO — Emisiones de línea base / proyecto'));
  tbl = el('table',{});
  tbl.appendChild(kv('Nb,p,y', fmt(r.D5,1), 'días/año'));
  tbl.appendChild(kv('Up,y', fmt(r.D6*100,1), '%'));
  tbl.appendChild(kv('Pb,adj', fmt(r.D8,5), 't/hogar/día'));
  tbl.appendChild(kv('Pp,adj', fmt(r.D9,5), 't/hogar/día'));
  tbl.appendChild(kv(`Emisiones LB ajustadas (${r.nYears} años, sin DAF)`, fmt(r.O63,3), 't CO2e'));
  tbl.appendChild(kv(`Ajuste DAF (${(r.dafPct*100).toFixed(0)}%)`, fmt(r.G12,3), 't CO2e'));
  tbl.appendChild(kv('BEy (línea base final)', fmt(r.BEy,3), 't CO2e'));
  tbl.appendChild(kv(`Emisiones proyecto (${r.nYears} años)`, fmt(r.O90,3), 't CO2e'));
  tbl.appendChild(kv('AEy (proyecto final)', fmt(r.AEy,3), 't CO2e'));
  tbl.appendChild(kv('Reducción bruta (BEy−AEy)', fmt(r.reduccionBruta,3), 't CO2e'));
  t4.appendChild(tbl);
  container.appendChild(t4);

  const t5 = el('div',{class:'card'});
  t5.appendChild(el('h3',{},'5) Fugas (LEy) y resultado final'));
  tbl = el('table',{});
  tbl.appendChild(kv('LEEmbodied,y', fmt(r.LEEmbodied,4), 't CO2e'));
  tbl.appendChild(kv('LEMarket,y', fmt(r.LEMarket,4), 't CO2e'));
  tbl.appendChild(kv('LEy total', fmt(r.LEy,4), 't CO2e'));
  tbl.appendChild(kv('HEind', fmt(r.HEind,2)));
  tbl.appendChild(kv('ERy (Reducción Neta Final)', fmt(r.ERy,4), 't CO2e'));
  t5.appendChild(tbl);
  container.appendChild(t5);

  const t6 = el('div',{class:'card'});
  t6.appendChild(el('h3',{},'Detalle por familia'));
  tbl = el('table',{});
  tbl.appendChild(el('tr',{}, ...['ID','AE','LB días','LB Cd','LB Cpc','Mon días','Mon Cd','Mon Cpc'].map(h=>el('th',{},h))));
  r.active.forEach((fam,i)=>{
    const lb = r.perFamLB[i], mo = r.perFamMon[i];
    tbl.appendChild(el('tr',{},
      el('td',{},fam.id), el('td',{},fmt(lb.AE,2)),
      el('td',{},lb.diasConDato), el('td',{},fmt(lb.cdPromedio,2)), el('td',{},fmt(lb.cpcPromedio,3)),
      el('td',{},mo.diasConDato), el('td',{},fmt(mo.cdPromedio,2)), el('td',{},fmt(mo.cpcPromedio,3))
    ));
  });
  t6.appendChild(tbl);
  container.appendChild(t6);
}

/* ---------- RENDER MASTER ---------- */
function renderAll(){
  renderNav();
  const main = document.getElementById('mainContent');
  main.innerHTML = '';
  if(currentTab==='config') renderConfig(main);
  else if(currentTab==='lb') renderLB(main);
  else if(currentTab==='mon') renderMon(main);
  else if(currentTab==='res') renderRes(main);
  saveState();
}

/* ---------- INIT ---------- */
if(!loadState()) STATE.families = JSON.parse(JSON.stringify(INITIAL_FAMILIES));
renderAll();
console.log('core loaded ok');
</script>
</body>
</html>
