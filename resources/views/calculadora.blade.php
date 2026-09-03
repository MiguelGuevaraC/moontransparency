<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>KPT & Precisión Estadística</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
  <h1 class="text-center mb-4">Proyecto Línea Base</h1>

  <!-- Tabs -->
  <ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="kpt-tab" data-bs-toggle="tab" data-bs-target="#kpt" type="button" role="tab">KPT</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="kpt-ajustado-tab" data-bs-toggle="tab" data-bs-target="#kptAjustado" type="button" role="tab">KPT Proyecto</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="precision-tab" data-bs-toggle="tab" data-bs-target="#precision" type="button" role="tab">PRECISION EST</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="topes-tab" data-bs-toggle="tab" data-bs-target="#topes" type="button" role="tab">TOPES</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="anyo1-tab" data-bs-toggle="tab" data-bs-target="#anyo1" type="button" role="tab">N<sub>p,y</sub></button>
    </li>
    <li class="nav-item" role="presentation">      
      <button class="nav-link" id="upy-tab" data-bs-toggle="tab" data-bs-target="#upy" type="button" role="tab">U p,y</button>
    </li>
    <li class="nav-item" role="presentation">      
      <button class="nav-link" id="calculo-tab" data-bs-toggle="tab" data-bs-target="#calculo" type="button" role="tab">CALCULO</button>
    </li>
  </ul>

  <div class="tab-content mt-3" id="myTabContent">
    <!-- KPT TAB -->
    <div class="tab-pane fade show active" id="kpt" role="tabpanel">
      <h3>Consumo Diario - 7 Días</h3>
      <form id="consumoForm">
        <div class="row mb-3">
          <div class="col-md-3">
            <label>Niños (0–14)</label>
            <input type="number" id="ninos" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Mujeres (&gt;14)</label>
            <input type="number" id="mujeres" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Hombres (15–59)</label>
            <input type="number" id="hombresJovenes" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Hombres (&gt;59)</label>
            <input type="number" id="hombresMayores" class="form-control" min="0" value="0">
          </div>
        </div>
        <h5>Consumo por día (kg)</h5>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Día</th>
              <th>Peso inicial (kg)</th>
              <th>Peso final (kg)</th>
              <th>Peso carbón (kg)</th>
              <th>Personas por hogar</th>
            </tr>
          </thead>
          <tbody>
            <tr class="filaDia">
              <td>1</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>2</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>3</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>4</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>5</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>6</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDia">
              <td>7</td>
              <td><input type="number" class="form-control pesoInicial" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinal" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbon" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDia" min="1" step="1" value="0" readonly></td>
            </tr>
          </tbody>
        </table>

        <button type="button" id="btnCalcular" class="btn btn-primary">Calcular</button>
      </form>

      <div id="resultado" class="alert alert-info mt-3" style="display:none;" >
        <p><strong>Adultos Equivalentes:</strong> <span id="ae"></span></p>
        <h5>Resultados por día</h5>
        <table class="table table-sm table-striped" id="tablaResultados">
          <thead class="table-light">
            <tr>
              <th>Día</th>
              <th>Consumo Diario (Cd)</th>
              <th>Consumo Per Cápita (Cpc)</th>
              <th>Peso carbón (kg)</th>
            </tr>
          </thead>
          <tbody>
            <!-- Aquí se insertan dinámicamente las filas desde el script -->
          </tbody>
        </table>

        <h5>Promedios</h5>
        <p><strong>Promedio Cd:</strong> <span id="cdProm"></span> kg/día</p>
        <p><strong>Promedio Cpc:</strong> <span id="cpcProm"></span> kg/persona/día</p>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label"><strong>Valor crítico T:</strong></label>
            <input type="number" id="tvalor" class="form-control" step="0.01" value="1.33">
          </div>
        </div>
      </div>
    </div>

    <!-- KPT AJUSTADO TAB -->
    <div class="tab-pane fade" id="kptAjustado" role="tabpanel">
      <h3>KPT Ajustado</h3>
      <div class="alert alert-secondary mt-3">
        <p>Calcula el consumo diario con proyecto y su ajuste para P<sub>p,adj</sub>.</p>
      </div>
      <form id="kptAjustadoForm">
        <div class="row mb-3">
          <div class="col-md-3">
            <label>Niños (0–14)</label>
            <input type="number" id="ninosAdj" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Mujeres (&gt;14)</label>
            <input type="number" id="mujeresAdj" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Hombres (15–59)</label>
            <input type="number" id="hombresJovenesAdj" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label>Hombres (&gt;59)</label>
            <input type="number" id="hombresMayoresAdj" class="form-control" min="0" value="0">
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Factor de ajuste del proyecto</label>
            <input type="number" id="factorProyecto" class="form-control" step="0.01" min="0" max="1" value="0.90">
          </div>
        </div>
        <h5>Consumo por día (kg) - Ajustado</h5>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Día</th>
              <th>Peso inicial (kg)</th>
              <th>Peso final (kg)</th>
              <th>Peso carbón (kg)</th>
              <th>Personas por hogar</th>
            </tr>
          </thead>
          <tbody>
            <tr class="filaDiaAdj">
              <td>1</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>2</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>3</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>4</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>5</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>6</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
            <tr class="filaDiaAdj">
              <td>7</td>
              <td><input type="number" class="form-control pesoInicialAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoFinalAdj" step="0.01"></td>
              <td><input type="number" class="form-control pesoCarbonAdj" step="0.01" value="0"></td>
              <td><input type="number" class="form-control personasDiaAdj" min="1" step="1" value="0" readonly></td>
            </tr>
          </tbody>
        </table>
        <button type="button" id="btnCalcularAjustado" class="btn btn-primary">Calcular KPT Ajustado</button>
      </form>

      <div id="resultadoAjustado" class="alert alert-info mt-3" style="display:none;" >
        <p><strong>Adultos Equivalentes:</strong> <span id="aeAdj"></span></p>
        <h5>Resultados por día</h5>
        <table class="table table-sm table-striped" id="tablaResultadosAjustado">
          <thead class="table-light">
            <tr>
              <th>Día</th>
              <th>Consumo Diario (Cd)</th>
              <th>Consumo Per Cápita (Cpc)</th>
              <th>Peso carbón (kg)</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>

        <h5>Promedios</h5>
        <p><strong>Promedio Cd:</strong> <span id="cdPromAdj"></span> kg/día</p>
        <p><strong>Promedio Cpc:</strong> <span id="cpcPromAdj"></span> kg/persona/día</p>
        <p><strong>P<sub>b,stat,adj</sub>:</strong> <span id="pbStatAdj"></span> kg/día</p>
        <p><strong>P<sub>b,adj,proj</sub>:</strong> <span id="pbAdjProj"></span> kg/día</p>
        <p><strong>P<sub>p,adj</sub>:</strong> <span id="ppAdj"></span> kg/día</p>
      </div>
    </div>

    <!-- PRECISION EST TAB -->
    <div class="tab-pane fade" id="precision" role="tabpanel">
      <h3>Precisión Estadística</h3>
      <div id="precisionKptResults" class="alert alert-info mt-3" style="display:none;">
        <h5>Precisión KPT</h5>
        <p><strong>Valor crítico T:</strong> <span id="kptTvalor"></span></p>
        <p><strong>Promedio (X̄):</strong> <span id="kptMean"></span> kg/día</p>
        <p><strong>Desviación estándar:</strong> <span id="kptStd"></span> kg/día</p>
        <p><strong>Muestra (n):</strong> <span id="kptN"></span></p>
        <p><strong>Error estándar:</strong> <span id="kptSe"></span> kg/día</p>
        <p><strong>Margen de error absoluto:</strong> <span id="kptMe"></span> kg/día</p>
        <p><strong>% Precisión:</strong> <span id="kptPrec"></span></p>
        <p><strong>Pb,stat:</strong> <span id="kptPb"></span></p>
      </div>
    </div>
    <div class="tab-pane fade" id="anyo1" role="tabpanel">
      <h3>Año 1</h3>
      <div class="alert alert-secondary mt-3">
        <p>Tabla del año 1. Solo el primer item es editable para calcular N<sub>p,y</sub>.</p>
      </div>
      <div class="mb-3">
        <p><em>N<sub>p,y</sub> = (Σ q<sub>j</sub> × m<sub>j,y</sub>) × 365 / 12</em></p>
      </div>
      <table class="table table-bordered table-sm mb-3">
        <thead class="table-light">
          <tr>
            <th>Item</th>
            <th>ID del Hogar</th>
            <th>¿Cantidad?</th>
            <th>¿Está operativa? (S/N)</th>
            <th>Día de inicio de funcionamiento</th>
            <th>¿Cuántos meses estuvo operativo en el año?</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><input type="text" id="idHogar1" class="form-control" value="1"></td>
            <td><input type="number" id="cantidad1" class="form-control" min="0" value="1"></td>
            <td>
              <select id="operativa1" class="form-select">
                <option value="S" selected>S</option>
                <option value="N">N</option>
              </select>
            </td>
            <td><input type="date" id="inicio1" class="form-control"></td>
            <td><input type="number" id="meses1" class="form-control" min="0" max="12" value="12"></td>
          </tr>
        </tbody>
      </table>
      <button type="button" id="btnCalcularNp" class="btn btn-primary mb-3">Calcular N<sub>p,y</sub></button>
      <div class="alert alert-info" id="anyo1Result" style="display:none;">
        <p><strong>N<sub>p,y</sub>:</strong> <span id="npResult"></span> días/año</p>
      </div>
    </div>
    <div class="tab-pane fade" id="upy" role="tabpanel">
      <h3>U p,y</h3>
      <div class="alert alert-secondary mt-3">
        <p>Ingresa la cantidad de cocinas por rango de edad, el porcentaje de uso y el primer Upky fijo en 0.75.</p>
      </div>
      <table class="table table-bordered table-sm mb-3">
        <thead class="table-light">
          <tr>
            <th>ITEM</th>
            <th>Rango de edad (años)</th>
            <th>Cantidad de cocinas</th>
            <th>Porcentaje de uso</th>
            <th>Valor de Uso individual (Upky)</th>
            <th>q × uso × Upky</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>0 - 1</td>
            <td><input type="number" id="cantidad1_upy" class="form-control" min="0" value="1"></td>
            <td><input type="number" id="uso1_upy" class="form-control" min="0" max="1" step="0.01" value="0.9" readonly="readonly"></td>
            <td><input type="number" id="upky1" class="form-control" min="0" step="0.01" value="0.75" readonly="readonly"></td>
            <td><span id="producto1_upy">37.50</span></td>
          </tr>
          <tr>
            <td>2</td>
            <td>1 - 2</td>
            <td><input type="number" id="cantidad2_upy" class="form-control" min="0" value="0"></td>
            <td><input type="number" id="uso2_upy" class="form-control" min="0" max="1" step="0.01" value="0.9" readonly="readonly"></td>
            <td><input type="number" id="upky2" class="form-control" min="0" step="0.01" value="0.75" readonly="readonly"></td>
            <td><span id="producto2_upy">18.00</span></td>
          </tr>
          <tr>
            <td>3</td>
            <td>2 - 3</td>
            <td><input type="number" id="cantidad3_upy" class="form-control" min="0" value="0"></td>
            <td><input type="number" id="uso3_upy" class="form-control" min="0" max="1" step="0.01" value="0.9" readonly="readonly"></td>
            <td><input type="number" id="upky3" class="form-control" min="0" step="0.01" value="0.75" readonly="readonly"></td>
            <td><span id="producto3_upy">9.80</span></td>
          </tr>
        </tbody>
      </table>
      <button type="button" id="btnCalcularUpy" class="btn btn-primary mb-3">Calcular U p,y</button>
      <div class="alert alert-info" id="upyResultCard" style="display:none;">
        <p><strong>U p,y:</strong> <span id="upyResult"></span></p>
      </div>
    </div>
      
    <div class="tab-pane fade" id="topes" role="tabpanel">
      <h3>Topes</h3>
      <div class="alert alert-secondary mt-3">
        <p>El tamaño promedio familiar de la muestra se toma directamente de las personas ingresadas en el tab <strong>KPT</strong>.</p>
      </div>
      <div id="topesResults" class="alert alert-info mt-3" style="display:none;">
        <h5>Topes calculados</h5>
        <p><strong>PCAP estándar:</strong> <span id="topesPcap"></span> t/año/persona</p>
        <p><strong>PCAP KPT calculado:</strong> <span id="topesKpt"></span> t/año/persona</p>
        <p><strong>PCAP usado:</strong> <span id="topesUsedPcap"></span> t/año/persona</p>
        <p><strong>Tamaño promedio familiar de la muestra (personas):</strong> <span id="topesHouseholdSize"></span></p>
        <p><strong>Tope diario por hogar:</strong> <span id="topesTPerHousehold"></span> t/hogar/año</p>
        <p><strong>Tope diario por hogar:</strong> <span id="topesKgPerHousehold"></span> kg/hogar/día</p>
        <p><strong>Pb,stat:</strong> <span id="topesPbStat"></span> kg/día</p>
        <p><strong>Pb,adj:</strong> <span id="topesPbAdj"></span> kg/día</p>
        <p><strong>Resultado final:</strong> <span id="topesMessage"></span></p>
      </div>
    </div>
    <div class="tab-pane fade" id="calculo" role="tabpanel">
      <h3>Cálculo</h3>
      <div class="alert alert-secondary mt-3">
        <p>Resultados del año 1 (línea base fogón).</p>
      </div>
      <div class="mb-3">
        <p><em>BE<sub>unadj,y</sub> = Σ<sub>b,p</sub> (N<sub>b,p,y</sub> × U<sub>p,y</sub> × P<sub>b,mean</sub> × NCV<sub>b,fuel</sub> × (EF<sub>b,f,CO2</sub> × fNRB<sub>b,y</sub> + EF<sub>b,f,non-CO2</sub>))</em></p>
      </div>
      <table class="table table-bordered table-sm mb-4">
        <thead class="table-light">
          <tr>
            <th>Factor</th>
            <th>Concepto</th>
            <th>Valor</th>
            <th>Origen de la información</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>N<sub>b,p,y</sub></td>
            <td>Días totales del grupo en monitoreo</td>
            <td><span id="summary_npy">-</span></td>
            <td>Tab N<sub>p,y</sub></td>
          </tr>
          <tr>
            <td>U<sub>p,y</sub></td>
            <td>Tasa de adopción/uso real</td>
            <td><span id="summary_upy">-</span></td>
            <td>Tab U p,y</td>
          </tr>
          <tr>
            <td>P<sub>b,mean</sub> (t/día)</td>
            <td>Consumo tradicional diario promedio</td>
            <td><span id="summary_pbmean">-</span></td>
            <td>Tab KPT / Baseline</td>
          </tr>
          <tr>
            <td>P<sub>b,adj</sub> (t/día)</td>
            <td>Consumo tradicional diario ajustado</td>
            <td><span id="summary_pbadj">-</span></td>
            <td>Tab TOPES</td>
          </tr>
          <tr>
            <td>P<sub>p,adj</sub> (t/día)</td>
            <td>Consumo diario con proyecto</td>
            <td><span id="summary_ppadj">-</span></td>
            <td>Tab KPT / Proyecto</td>
          </tr>
          <tr>
            <td>NCV<sub>b,fuel</sub> (TJ/t)</td>
            <td>Poder calorífico de la leña</td>
            <td><span id="summary_ncv">0.0156</span></td>
            <td>Valor IPCC predeterminado</td>
          </tr>
          <tr>
            <td>EF<sub>b,f,CO2</sub> (tCO<sub>2</sub>/TJ)</td>
            <td>Factor de emisión CO2</td>
            <td><span id="summary_efco2">112.00</span></td>
            <td>Valor IPCC estándar</td>
          </tr>
          <tr>
            <td>fNRB<sub>b,y</sub></td>
            <td>Fracción de biomasa no renovable</td>
            <td><span id="summary_fnrb">0.8</span></td>
            <td>Valor de línea base</td>
          </tr>
          <tr>
            <td>EF<sub>b,f,non-CO2</sub> (tCO<sub>2</sub>e/TJ)</td>
            <td>Factor acumulado no-CO2</td>
            <td><span id="summary_efnonco2">9.49</span></td>
            <td>Valor AR6</td>
          </tr>
        </tbody>
      </table>
      <h4>Resultados del cálculo de línea base</h4>
      <table class="table table-bordered table-sm" id="tbBaseline">
        <thead class="table-light">
          <tr>
            <th>Año</th>
            <th>N<sub>b,p,y</sub></th>
            <th>U<sub>p,y</sub></th>
            <th>P<sub>b,mean</sub></th>
            <th>NCV<sub>b,fuel</sub></th>
            <th>EF<sub>b,f,CO2</sub></th>
            <th>fNRB<sub>b,y</sub></th>
            <th>EF<sub>b,f,non-CO2</sub></th>
            <th>BE<sub>unadj,y</sub></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td id="table_npy">912.50</td>
            <td id="table_upy">74%</td>
            <td id="table_pbmean">0.02</td>
            <td>0.0156</td>
            <td>112.00</td>
            <td>0.8</td>
            <td>9.49</td>
            <td id="table_beunadj">16.97</td>
          </tr>
        </tbody>
      </table>
      <h4>Resultados del cálculo de línea base ajustada</h4>
      <table class="table table-bordered table-sm mt-4" id="tbBaselineAdj">
        <thead class="table-light">
          <tr>
            <th>Año</th>
            <th>N<sub>b,p,y</sub></th>
            <th>U<sub>p,y</sub></th>
            <th>P<sub>b,adj</sub></th>
            <th>NCV<sub>b,fuel</sub></th>
            <th>EF<sub>b,f,CO2</sub></th>
            <th>fNRB<sub>b,y</sub></th>
            <th>EF<sub>b,f,non-CO2</sub></th>
            <th>BE<sub>unadj,y</sub></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td id="table_npy_adj">912.50</td>
            <td id="table_upy_adj">74%</td>
            <td id="table_pbadj">0.02</td>
            <td>0.0156</td>
            <td>112.00</td>
            <td>0.8</td>
            <td>9.49</td>
            <td id="table_beunadj_adj">16.97</td>
          </tr>
        </tbody>
      </table>
      <h4>Resultados del cálculo con el proyecto (Pp,adj)</h4>
      <table class="table table-bordered table-sm mt-4" id="tbBaselinePpAdj">
        <thead class="table-light">
          <tr>
            <th>Año</th>
            <th>N<sub>b,p,y</sub></th>
            <th>U<sub>p,y</sub></th>
            <th>P<sub>p,adj</sub></th>
            <th>NCV<sub>b,fuel</sub></th>
            <th>EF<sub>b,f,CO2</sub></th>
            <th>fNRB<sub>b,y</sub></th>
            <th>EF<sub>b,f,non-CO2</sub></th>
            <th>BE<sub>unadj,y</sub></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td id="table_npy_ppadj">912.50</td>
            <td id="table_upy_ppadj">74%</td>
            <td id="table_ppadj">0.02</td>
            <td>0.0156</td>
            <td>112.00</td>
            <td>0.8</td>
            <td>9.49</td>
            <td id="table_beunadj_ppadj">16.97</td>
          </tr>
        </tbody>
      </table>

      <div class="alert alert-secondary mt-3">
        <p>Resultados resumen del cálculo de consumo y Pb ajustado.</p>
      </div>
      <div class="alert alert-info mt-3">
        <p><strong>Promedio Cd:</strong> <span id="calcCdProm">-</span> kg/día</p>
        <p><strong>Promedio Cpc:</strong> <span id="calcCpcProm">-</span> kg/persona/día</p>
        <p><strong>Pb,adj:</strong> <span id="calcPbAdj">-</span> kg/día</p>
      </div>

      <!-- REDUCCIÓN NETA DE EMISIONES ERy -->
      <h4 class="mt-5">Reducción Neta de Emisiones, ER<sub>y</sub></h4>
      
      <div class="alert alert-secondary mt-3">
        <p>Cálculo de la reducción neta de emisiones (ER<sub>y</sub>) según la fórmula:</p>
        <p><em>ER<sub>y</sub> = ((BE<sub>y</sub> − AE<sub>y</sub>) × HE<sub>ind</sub>) − LE<sub>y</sub></em></p>
      </div>

      <div class="row mb-3 mt-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-light">
              <h5>Datos de Emisiones</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label"><strong>Año de Monitoreo</strong></label>
                <select id="erAnyo" class="form-select">
                  <option value="2026">2026 (DAF = 0.02)</option>
                  <option value="2027">2027 (DAF = 0.04)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>DAF<sub>NezZero</sub></strong> (Factor de Ajuste)</label>
                <input type="number" id="erDafNezZero" class="form-control" step="0.01" readonly value="0.02">
                <small class="text-muted">Descuento obligatorio de mitigación según año</small>
              </div>
              <div class="alert alert-info mt-2">
                <small><strong>Fórmula:</strong> BE<sub>adj,y</sub> = BE<sub>unc,y</sub> × (1 − DAF<sub>NezZero</sub>)</small>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>BE<sub>unc,y</sub></strong> (Emisiones sin ajuste) (t CO2e)</label>
                <input type="number" id="erBeyUnc" class="form-control" step="0.01" readonly>
                <small class="text-muted">Valor de BE<sub>unadj,y</sub> de la línea base ajustada</small>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Emisiones de Línea Base Final (BE<sub>adj,y</sub>)</strong> (t CO2e)</label>
                <input type="number" id="erBey" class="form-control" step="0.01" readonly>
                <small class="text-muted">BE<sub>unc,y</sub> con descuento DAF aplicado</small>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Emisiones del Proyecto Final (AE<sub>y</sub>)</strong> (t CO2e)</label>
                <input type="number" id="erAey" class="form-control" step="0.01" readonly>
                <small class="text-muted">Se calcula automáticamente desde BE<sub>unadj,y</sub> (Pp,adj)</small>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Reducción Bruta</strong> (t CO2e)</label>
                <input type="number" id="erReduccionBruta" class="form-control" step="0.01" readonly>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-light">
              <h5>Efecto Hawthorne (HE<sub>ind</sub>)</h5>
            </div>
            <div class="card-body">
              <div class="alert alert-secondary">
                <p><strong>Opción A:</strong> Monitoreo Manual Tradicional: Si realizas el monitoreo mediante encuestas en papel y visitas físicas de técnicos para pesar la leña, estás obligado a aplicar una penalización automática. El factor HEind aplicable por los años 2026-2027 es de 0.90 (un descuento directo del 10% del ahorro bruto).</p>
                <p><strong>Opción B:</strong> Monitoreo Digital Continuo: Si instalas sensores electrónicos de temperatura (SUMs) en las cocinas para registrar el uso real de forma remota sin alterar la rutina del usuario, estás exento de la penalización y tu factor HEind es 1.0.</p>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Selecciona tipo de monitoreo:</strong></label>
                <select id="erTipoMonitoreo" class="form-select">
                  <option value="manual">Opción A: Monitoreo Manual Tradicional (HEind = 0.90)</option>
                  <option value="digital">Opción B: Monitoreo Digital Continuo (HEind = 1.0)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>HE<sub>ind</sub></strong></label>
                <input type="number" id="erHeind" class="form-control" step="0.01" readonly value="0.90">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-light">
              <h5>Pérdidas y Fugas (LE<sub>y</sub>)</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label"><strong>LE<sub>y</sub></strong> (t CO2e) - Pérdidas y fugas</label>
                <input type="number" id="erLey" class="form-control" step="0.01" placeholder="0" value="0">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-12">
          <button type="button" id="btnCalcularEry" class="btn btn-primary btn-lg">Calcular ER<sub>y</sub></button>
        </div>
      </div>

      <div id="resultadoEry" class="alert alert-success mt-4" style="display:none;">
        <div class="row">
          <div class="col-md-6">
            <div class="card bg-light">
              <div class="card-body">
                <h5 class="card-title">Cálculo Detallado</h5>
                <p><strong>Reducción Bruta:</strong> BE<sub>y</sub> - AE<sub>y</sub> = <span id="resultReduccionBruta">-</span> t CO2e</p>
                <p><strong>Reducción Bruta × HE<sub>ind</sub>:</strong> <span id="resultReduccionAjustada">-</span> t CO2e</p>
                <p><strong>Pérdidas y Fugas (LE<sub>y</sub>):</strong> <span id="resultLey">-</span> t CO2e</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card bg-success text-white">
              <div class="card-body">
                <h5 class="card-title">ER<sub>y</sub> (Reducción Neta de Emisiones)</h5>
                <h2 id="resultEry">-</h2>
                <p class="small">t CO2e</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    // --- KPT ---
  $("#btnCalcular").click(function(){
    const ninos = parseInt($("#ninos").val())||0;
    const mujeres = parseInt($("#mujeres").val())||0;
    const hombresJovenes = parseInt($("#hombresJovenes").val())||0;
    const hombresMayores = parseInt($("#hombresMayores").val())||0;

    const factorNinos=0.5, factorMujeres=0.8, factorHombresJovenes=1, factorHombresMayores=0.8;
    const ae = (ninos*factorNinos)+(mujeres*factorMujeres)+(hombresJovenes*factorHombresJovenes)+(hombresMayores*factorHombresMayores);
    const totalPersons = ninos + mujeres + hombresJovenes + hombresMayores;
    $(".personasDia").val(totalPersons);

    let suma=0, count=0;
    const cdValues = [];
    let totalPersonasDias = 0;
    $("#tablaResultados tbody").empty();

      $(".filaDia").each(function(i){
      const pesoInicial = parseFloat($(this).find(".pesoInicial").val());
      const pesoFinal   = parseFloat($(this).find(".pesoFinal").val());
      const pesoCarbon  = parseFloat($(this).find(".pesoCarbon").val());
      const personasDia = parseFloat($(this).find(".personasDia").val());

      if(!isNaN(pesoInicial) && !isNaN(pesoFinal)){
        const cd = pesoInicial - pesoFinal - pesoCarbon; // consumo del día
        suma += cd; count++;
        cdValues.push(cd);
        if (!isNaN(personasDia) && personasDia > 0) totalPersonasDias += personasDia;
        const cpc = (ae > 0) ? (cd / ae) : NaN;

        $("#tablaResultados tbody").append(
          `<tr>
             <td>${i+1}</td>
             <td>${cd.toFixed(2)}</td>
             <td>${!isNaN(cpc) ? cpc.toFixed(2) : "-"}</td>
             <td>${!isNaN(pesoCarbon) ? pesoCarbon.toFixed(2) : "-"}</td>
           </tr>`
        );
      } else {
        $("#tablaResultados tbody").append(
          `<tr><td>${i+1}</td><td>-</td><td>-</td><td>${!isNaN(pesoCarbon) ? pesoCarbon.toFixed(2) : "-"}</td></tr>`
        );
      }
    });

    if(ae>0 && count>0){
      const promCd = suma / count;
      const promCpc = promCd / ae;
      const std = count > 1 ? Math.sqrt(cdValues.reduce((a,b) => a + Math.pow(b - promCd, 2), 0) / (count - 1)) : 0;
      const se = std / Math.sqrt(count);
      const tvalor = parseFloat($("#tvalor").val()) || 0;
      const me = tvalor > 0 ? tvalor * se : 0;
      const prec = promCd > 0 ? (me / promCd) * 100 : 0;
      const pbStat = prec <= 10 ? promCd : promCd - me;

      const pcapDefault = 1.25;
      const kptT = totalPersonasDias > 0 ? (suma / totalPersonasDias * 365 / 1000) : 0;
      const usedPcap = kptT > pcapDefault ? pcapDefault : kptT;
      const householdTopeT = usedPcap * totalPersons / 365;
      const householdTopeKg = householdTopeT * 1000;
      const pbAdj = Math.min(pbStat, householdTopeKg);
      const topeMessage = pbAdj === pbStat
        ? `Como ${pbStat.toFixed(2)} kg/día es menor que el tope de ${householdTopeKg.toFixed(2)} kg/día, tu Pb,adj se queda en ${pbAdj.toFixed(2)} kg/día.`
        : `Como ${pbStat.toFixed(2)} kg/día es mayor que el tope de ${householdTopeKg.toFixed(2)} kg/día, tu Pb,adj se queda en ${pbAdj.toFixed(2)} kg/día.`;

      $("#ae").text(ae.toFixed(2));
      $("#cdProm").text(promCd.toFixed(2));
      $("#cpcProm").text(promCpc.toFixed(2));
      $("#kptMean").text(promCd.toFixed(2));
      $("#kptTvalor").text(tvalor.toFixed(2));
      $("#kptStd").text(std.toFixed(5));
      $("#kptN").text(count);
      $("#kptSe").text(se.toFixed(2));
      $("#kptMe").text(me.toFixed(2));
      $("#kptPrec").text(prec.toFixed(2) + "%");
      $("#kptPb").text(pbStat.toFixed(2));
      $("#calcCdProm").text(promCd.toFixed(2));
      $("#calcCpcProm").text(promCpc.toFixed(2));
      $("#calcPbAdj").text(pbAdj.toFixed(2));
      $("#summary_pbmean").text((promCd/1000).toFixed(3));
      $("#table_pbmean").text((promCd/1000).toFixed(3));
      $("#precisionKptResults").show();

      $("#topesPcap").text(pcapDefault.toFixed(2));
      $("#topesKpt").text(kptT.toFixed(2));
      $("#topesUsedPcap").text(usedPcap.toFixed(2));
      $("#topesHouseholdSize").text(ae);
      $("#topesTPerHousehold").text(householdTopeT.toFixed(4));
      $("#topesKgPerHousehold").text(householdTopeKg.toFixed(2));
      $("#topesPbStat").text(pbStat.toFixed(2));
      $("#topesPbAdj").text(pbAdj.toFixed(2));
      $("#topesMessage").text(topeMessage);
      $("#summary_pbadj").text((pbAdj/1000).toFixed(3));
      $("#table_pbadj").text((pbAdj/1000).toFixed(3));
      const ppAdjValue = parseFloat($("#summary_ppadj").text()) || 0;
      var peunadj = (parseFloat($("#npResult").text()) || 0) * (parseFloat($("#upyResult").text()) || 0) /100 * (parseFloat($("#table_pbmean").text()) || 0) * 0.0156 * (112 * 0.8 + 9.49);
      var peunadj2 = (parseFloat($("#npResult").text()) || 0) * (parseFloat($("#upyResult").text()) || 0) /100 * (parseFloat($("#summary_pbadj").text()) || 0) * 0.0156 * (112 * 0.8 + 9.49);
      var peunadj3 = (parseFloat($("#npResult").text()) || 0) * (parseFloat($("#upyResult").text()) || 0) /100 * ppAdjValue * 0.0156 * (112 * 0.8 + 9.49);
      $("#table_beunadj").text((peunadj).toFixed(3));
      $("#table_beunadj_adj").text((peunadj2).toFixed(3));
      $("#table_beunadj_ppadj").text((peunadj3).toFixed(3));
      $("#table_npy").text($("#npResult").text());
      $("#table_npy_adj").text($("#npResult").text());
      $("#table_npy_ppadj").text($("#npResult").text());
      $("#table_upy_ppadj").text($("#upyResult").text());
      $("#table_ppadj").text(ppAdjValue.toFixed(3));
      $("#summary_npy").text($("#npResult").text());
      $("#topesResults").show();
      $("#resultado").show();
    } else {
      alert("Ingresa valores válidos.");
    }
  });

  $("#btnCalcularNp").click(function(){
    const cantidad = parseFloat($("#cantidad1").val()) || 0;
    const operativa = $("#operativa1").val();
    const meses = parseFloat($("#meses1").val()) || 0;
    const factorOperativo = operativa === "S" ? 1 : 0;
    const np = factorOperativo * cantidad * meses * 365 / 12;
    $("#npResult").text(np.toFixed(2));
    $("#anyo1Result").show();
    $("#summary_npy").text(np.toFixed(2));
    $("#table_npy").text(np.toFixed(2));
    $("#table_npy_adj").text(np.toFixed(2));
    $("#table_npy_ppadj").text(np.toFixed(2));
  });

  function actualizarUpy() {
    const q1 = parseFloat($("#cantidad1_upy").val()) || 0;
    const u1 = parseFloat($("#uso1_upy").val()) || 0;
    const upky1 = 0.75;
    const q2 = parseFloat($("#cantidad2_upy").val()) || 0;
    const u2 = parseFloat($("#uso2_upy").val()) || 0;
    const upky2 = parseFloat($("#upky2").val()) || 0;
    const q3 = parseFloat($("#cantidad3_upy").val()) || 0;
    const u3 = parseFloat($("#uso3_upy").val()) || 0;
    const upky3 = parseFloat($("#upky3").val()) || 0;

    const prod1 = q1 * upky1;
    const prod2 = q2 * upky2;
    const prod3 = q3 * upky3;
    const total = prod1 + prod2 + prod3;
    const totalCocinas = q1 + q2 + q3;

    $("#producto1_upy").text(prod1.toFixed(2));
    $("#producto2_upy").text(prod2.toFixed(2));
    $("#producto3_upy").text(prod3.toFixed(2));
    $("#upyResult").text(total.toFixed(2) + "%");
    $("#summary_upy").text(total.toFixed(2) + "%");
    $("#table_upy").text(total.toFixed(2) + "%");
    $("#table_upy_adj").text(total.toFixed(2) + "%");
    $("#table_upy_ppadj").text(total.toFixed(2) + "%");
    $("#upyResultCard").show();
  }

  function calcularKptAjustado() {
    const ninos = parseInt($("#ninosAdj").val()) || 0;
    const mujeres = parseInt($("#mujeresAdj").val()) || 0;
    const hombresJovenes = parseInt($("#hombresJovenesAdj").val()) || 0;
    const hombresMayores = parseInt($("#hombresMayoresAdj").val()) || 0;
    const factorProyecto = parseFloat($("#factorProyecto").val()) || 0;

    const factorNinos = 0.5;
    const factorMujeres = 0.8;
    const factorHombresJovenes = 1;
    const factorHombresMayores = 0.8;
    const ae = (ninos * factorNinos) + (mujeres * factorMujeres) + (hombresJovenes * factorHombresJovenes) + (hombresMayores * factorHombresMayores);
    const totalPersons = ninos + mujeres + hombresJovenes + hombresMayores;
    $(".personasDiaAdj").val(totalPersons);

    let suma = 0;
    let count = 0;
    const cdValues = [];
    $("#tablaResultadosAjustado tbody").empty();

    $(".filaDiaAdj").each(function(i){
      const pesoInicial = parseFloat($(this).find(".pesoInicialAdj").val());
      const pesoFinal = parseFloat($(this).find(".pesoFinalAdj").val());
      const pesoCarbon = parseFloat($(this).find(".pesoCarbonAdj").val());

      if(!isNaN(pesoInicial) && !isNaN(pesoFinal)) {
        const cd = pesoInicial - pesoFinal - pesoCarbon;
        suma += cd;
        count++;
        cdValues.push(cd);
        const cpc = (ae > 0) ? (cd / ae) : NaN;

        $("#tablaResultadosAjustado tbody").append(
          `<tr>
             <td>${i+1}</td>
             <td>${cd.toFixed(2)}</td>
             <td>${!isNaN(cpc) ? cpc.toFixed(2) : "-"}</td>
             <td>${!isNaN(pesoCarbon) ? pesoCarbon.toFixed(2) : "-"}</td>
           </tr>`
        );
      } else {
        $("#tablaResultadosAjustado tbody").append(
          `<tr><td>${i+1}</td><td>-</td><td>-</td><td>${!isNaN(pesoCarbon) ? pesoCarbon.toFixed(2) : "-"}</td></tr>`
        );
      }
    });

    if(ae > 0 && count > 0) {
      const promCd = suma / count;
      const promCpc = promCd / ae;
      const pbStatAdj = promCd;
      const pbAdjProj = promCd * factorProyecto;
      const ppAdj = pbAdjProj;

      $("#aeAdj").text(ae.toFixed(2));
      $("#cdPromAdj").text(promCd.toFixed(2));
      $("#cpcPromAdj").text(promCpc.toFixed(2));
      $("#pbStatAdj").text(pbStatAdj.toFixed(2));
      $("#pbAdjProj").text(pbAdjProj.toFixed(2));
      $("#ppAdj").text(ppAdj.toFixed(2));
      $("#summary_ppadj").text((ppAdj/1000).toFixed(3));
      $("#table_ppadj").text((ppAdj/1000).toFixed(3));
      $("#table_beunadj_ppadj").text(((parseFloat($("#npResult").text()) || 0) * (parseFloat($("#upyResult").text()) || 0) /100 * (ppAdj/1000) * 0.0156 * (112 * 0.8 + 9.49)).toFixed(3));
      $("#table_npy_ppadj").text($("#npResult").text());
      $("#table_upy_ppadj").text($("#upyResult").text());
      $("#resultadoAjustado").show();
    } else {
      alert("Ingresa valores válidos para KPT Ajustado.");
    }
  }

  $("#cantidad1_upy, #uso1_upy, #cantidad2_upy, #uso2_upy, #upky2, #cantidad3_upy, #uso3_upy, #upky3").on("input", actualizarUpy);
  $("#btnCalcularUpy").click(function() {
    // Ejecutar cálculo de Upy
    actualizarUpy();
    
    // Ejecutar cálculo de KPT (btnCalcular)
    $("#btnCalcular").click();
    
    // Ejecutar cálculo de Np (btnCalcularNp)
    $("#btnCalcularNp").click();
  });
  $("#btnCalcularAjustado").click(calcularKptAjustado);

  // --- ER_y Calculation ---
  function actualizarValoresEry() {
    const beUnadjLineaBaseAdj = parseFloat($("#table_beunadj_adj").text()) || 0;
    const beUnadjPpAdj = parseFloat($("#table_beunadj_ppadj").text()) || 0;
    const anyo = $("#erAnyo").val();
    const dafNezZero = anyo === "2026" ? 0.02 : 0.04;
    
    // Actualizar DAF
    $("#erDafNezZero").val(dafNezZero);
    
    // Calcular BE_adj,y = BE_unc,y × (1 - DAF_NezZero)
    const beUncY = beUnadjLineaBaseAdj;
    const beAdjY = beUncY * (1 - dafNezZero);
    
    $("#erBeyUnc").val(beUncY.toFixed(2));
    $("#erBey").val(beAdjY.toFixed(2));
    $("#erAey").val(beUnadjPpAdj.toFixed(2));
    
    // Calcular reducción bruta automáticamente
    const reduccionBruta = beAdjY - beUnadjPpAdj;
    $("#erReduccionBruta").val(reduccionBruta.toFixed(2));
  }

  // Actualizar DAF cuando cambia el año
  $("#erAnyo").change(function() {
    const anyo = $(this).val();
    const dafNezZero = anyo === "2026" ? 0.02 : 0.04;
    $("#erDafNezZero").val(dafNezZero);
  });

  $("#erTipoMonitoreo").change(function() {
    const tipoMonitoreo = $(this).val();
    const heind = tipoMonitoreo === "manual" ? 0.90 : 1.0;
    $("#erHeind").val(heind);
  });

  $("#btnCalcularEry").click(function() {
    actualizarValoresEry();
    
    const bey = parseFloat($("#erBey").val()) || 0;
    const aey = parseFloat($("#erAey").val()) || 0;
    const heind = parseFloat($("#erHeind").val()) || 1.0;
    const ley = parseFloat($("#erLey").val()) || 0;

    if (bey <= 0 || aey <= 0) {
      alert("Por favor ingresa valores válidos para BE_y y AE_y.");
      return;
    }

    const reduccionBruta = bey - aey;
    const reduccionAjustada = reduccionBruta * heind;
    const ery = reduccionAjustada - ley;

    $("#resultReduccionBruta").text(reduccionBruta.toFixed(2));
    $("#resultReduccionAjustada").text(reduccionAjustada.toFixed(2));
    $("#resultLey").text(ley.toFixed(2));
    $("#resultEry").text(ery.toFixed(2));
    $("#resultadoEry").show();
  });

});
</script>
</body>
</html>
