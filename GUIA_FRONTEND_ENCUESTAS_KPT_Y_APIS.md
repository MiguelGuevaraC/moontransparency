# Moon Transparency — Guía frontend y contrato de APIs

> Contrato derivado de los cambios comprendidos entre `7b0c890caa7ff430982496f25c26583f66a2095c` y `31758604853dc707bb2b0852695f623a55c61d20`, ambos incluidos.
>
> Formato estructural basado en `OFICIALGoldenBrasas_API_Admin.md`: índice, tabla por endpoint, body, respuestas y errores.

---

## Objetivo

Este documento indica qué debe implementar:

- Moon Group Admin;
- la web/formulario de encuestas;
- la aplicación móvil;
- la interfaz de la Calculadora de CO2;
- el Portal de Impacto.

Frontend debe tomar las reglas desde los metadatos del backend. No debe comparar nombres como `KPT línea base` ni asumir IDs fijos como `10` y `11`.

---

## Base URL, autenticación y permisos

**Base path:** `https://{dominio}/moontransparency/public/api`

### APIs autenticadas

```http
Authorization: Bearer {token}
Accept: application/json
```

| Función | Restricción adicional |
|---|---|
| Ver encuestas, vista previa e historial | permiso `surveys.view` |
| Crear/editar preguntas | permiso `surveys.manage` |
| Buscar hogares desde panel | permiso `participations.manage` |
| Ver participación | permiso `participations.view` |
| Ver contrato de calculadora | permiso `calculator.view` |
| Alertas | usuario con rol administrador |

### APIs públicas con UUID

```http
UUID: {uuid_de_acceso_publico}
Accept: application/json
```

Se usa en:

- `/surveys-public`;
- `/survey-show/{id}`;
- `/survey-show/{survey}/household-options`;
- `/calculator/co2/surveys`;
- `/calculator/co2/embed-link`.

### APIs públicas de captura

Las siguientes rutas no exigen Bearer ni UUID:

- `POST /response-survey`;
- `POST /response-survey/{id}`;
- `POST /response-survey/{id}/finalize`;
- `POST /offline-sync`.

---

## Índice de endpoints que debe integrar frontend

| Módulo | Endpoint | Método | Uso frontend |
|---|---|---|---|
| Editor | `/surveyquestion-field-types` | GET | Poblar tipo de respuesta, incluido DECIMAL |
| Encuesta admin | `/survey/{id}` | GET | Leer metadatos y vínculo PRE/POST |
| Encuesta pública | `/survey-show/{id}` | GET | Construir formulario de campo |
| Vista previa | `/survey/{id}/preview` | GET | Modal/página de solo lectura |
| Historial | `/survey/{id}/history` | GET | Modal de auditoría |
| Alertas | `/alerts` | GET | Bandeja de alertas |
| Alertas | `/alerts/unread-count` | GET | Badge de campana |
| Alertas | `/alerts/{id}/read` | PATCH | Marcar una alerta |
| Alertas | `/alerts/read-all` | PATCH | Marcar todas |
| Hogares admin | `/survey/{survey}/household-options` | GET | Selector de hogar POST |
| Hogares público | `/survey-show/{survey}/household-options` | GET | Selector de hogar POST en web/app |
| Crear borrador | `/response-survey` | POST | Primera captura |
| Actualizar borrador | `/response-survey/{id}` | POST | Guardar día o editar sin repetir DNI |
| Finalizar | `/response-survey/{id}/finalize` | POST | Validación final de siete días |
| Ver participación | `/surveyed/{id}` | GET | Cargar persona, días y respuestas para editar |
| Sync móvil | `/offline-sync` | POST | Enviar lote offline idempotente |
| Datos calculadora | `/surveyed/{id}/calculator` | GET | Contrato consolidado de una participación |
| Configuración CO2 | `/calculator/co2/configuration` | GET | Encuestas y parámetros de cálculo |
| Calcular CO2 | `/calculator/co2` | POST | Ejecutar cálculo desde encuestas |

---

## Reglas de implementación frontend

### 1. Editor de preguntas

- Obtener los tipos con `GET /surveyquestion-field-types`.
- Mostrar `NUMERICO` como entero y `DECIMAL` como decimal.
- Para `DECIMAL`, usar `inputmode="decimal"` y permitir punto o coma.
- Para `NUMERICO`, usar `step="1"` y no permitir fracciones.
- No mantener localmente una lista fija de tipos.

### 2. Render dinámico de KPT

Usar estos campos de `GET /survey/{id}` o `GET /survey-show/{id}`:

| Campo | Comportamiento |
|---|---|
| `supports_daily_measurements` | Mostrar o no navegación por días |
| `expected_days` | Crear las pestañas de día; actualmente vale `7` para KPT |
| `variants` | Mostrar selector de caso en monitoreo |
| `question.response_scope` | `PARTICIPATION`: una vez; `MEASUREMENT`: por día |
| `question.applicable_days` | Mostrar solo en esos días; `null` significa todos |
| `question.scenario` | Mostrar solo si coincide con `survey_variant` |
| `question.section` | Agrupar visualmente preguntas |
| `question.accepts_decimals` | Activar captura decimal |

No mostrar navegación por días cuando `supports_daily_measurements=false`.

### 3. Variantes de monitoreo

El usuario debe seleccionar una sola variante:

- `MONITORING_COMBINED`;
- `MONITORING_MOON_ONLY`.

Enviar el valor como `survey_variant`. Una participación no puede mezclar preguntas de ambos escenarios ni cambiar de escenario después de registrar respuestas.

Las participaciones finalizadas creadas con el instrumento anterior conservan su mismo ID y estado durante la migración. Si no contienen pesos suficientes para inferir el caso, `survey_variant` puede quedar `null`; frontend debe mostrarlas como históricas de solo lectura y no inventar una variante.

### 4. ID del hogar

Leer `household_identifier.mode`:

- `FREE_TEXT`: input libre para línea base;
- `SEARCHABLE_SELECT`: combo con búsqueda para monitoreo.

El selector debe usar el endpoint entregado por:

- `household_identifier.authenticated_options_endpoint`, o
- `household_identifier.options_endpoint`.

Enviar el código seleccionado como `household_code`. Si también se envía la respuesta de la pregunta de hogar, ambos valores deben coincidir.

### 5. Edición y DNI

- Al crear: `number_document` es obligatorio.
- Al editar/finalizar: puede omitirse.
- No mostrar un error local exigiendo nuevamente el DNI.
- Cargar `respondent`, `surveyed_responses` y `measurements` desde `GET /surveyed/{id}` en el panel autenticado.
- En móvil, conservar la persona y el `client_participation_id` en almacenamiento local.

### 6. Calculadora

- Cambiar el texto visible a **Calculadora de CO2**.
- Los campos KPT provenientes de encuestas deben ser de solo lectura.
- Después de guardar/sincronizar una encuesta, volver a consultar la configuración o el cálculo; no conservar una copia vieja en estado local.
- Vincular línea base y monitoreo por `household.id`/`household.code`, no por el ID de participación.
- Respetar `calculator_ready` en la respuesta offline.

### 7. Navegación y título

- No mostrar Inicio si el menú dinámico lo entrega inactivo; Proyectos será la entrada principal.
- Cambiar el `<title>` del portal a `Moon Group - Portal de Impacto`.
- No duplicar Inicio mediante rutas o menús hardcodeados.

---

## TIPOS DE RESPUESTA DEL EDITOR

### LISTAR TIPOS DE CAMPO

|  |  |
|---|---|
| **Endpoint** | `/surveyquestion-field-types` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` · Accept: application/json |
| **Permiso** | `surveys.view` |

**Body:** —

**Response 200:**

```json
{
  "data": [
    { "value": "NUMERICO", "label": "Numérico (entero)" },
    { "value": "DECIMAL", "label": "Decimal" },
    { "value": "FECHA", "label": "Fecha" },
    { "value": "LARGO", "label": "Párrafo largo" },
    { "value": "CORTO", "label": "Párrafo corto" }
  ]
}
```

### CREAR O ACTUALIZAR UNA PREGUNTA DECIMAL

|  | Crear | Actualizar |
|---|---|---|
| **Endpoint** | `/surveyquestion` | `/surveyquestion/{id}` |
| **Método** | POST | PUT |
| **Content-Type** | `multipart/form-data` | `multipart/form-data` |
| **Permiso** | `surveys.manage` | `surveys.manage` |

**Body mínimo de ejemplo:**

```json
{
  "survey_id": 10,
  "question_type": "LIBRE",
  "type_field": "DECIMAL",
  "question_text": "Peso inicial de leña",
  "order": 4,
  "is_required": true,
  "justification": "Ingrese el peso en kilogramos"
}
```

**Response 200:**

```json
{
  "data": {
    "id": 104,
    "survey_id": 10,
    "question_text": "Peso inicial de leña",
    "question_type": "LIBRE",
    "type_field": "DECIMAL",
    "accepts_decimals": true,
    "calculator_value_type": "number"
  }
}
```

**Error 422 — valor entero enviado a NUMERICO con decimales:**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "responses.0.response_text": ["La respuesta debe ser un número entero."]
  }
}
```

---

## DETALLE Y METADATOS DE ENCUESTA

### OBTENER ENCUESTA PARA RENDERIZAR

|  | Admin | Público/app |
|---|---|---|
| **Endpoint** | `/survey/{id}` | `/survey-show/{id}` |
| **Método** | GET | GET |
| **Headers** | Bearer `{token}` | `UUID: {uuid}` |
| **Acceso** | `surveys.view` | encuesta activa |

**Response 200 resumida — KPT monitoreo:**

```json
{
  "data": {
    "id": 11,
    "code": "KPT_CO2_MONITORING",
    "survey_name": "KPT monitoreo",
    "survey_type": "POST",
    "kind": "MONITORING",
    "supports_daily_measurements": true,
    "expected_days": 7,
    "variants": {
      "MONITORING_COMBINED": {
        "label": "Cocina tradicional + cocina mejorada Moon Group",
        "description": "CASO 1 del instrumento de monitoreo."
      },
      "MONITORING_MOON_ONLY": {
        "label": "Solo cocina mejorada Moon Group",
        "description": "CASO 2 del instrumento de monitoreo."
      }
    },
    "household_identifier": {
      "survey_question_id": 201,
      "required": true,
      "mode": "SEARCHABLE_SELECT",
      "uniqueness": "GLOBAL",
      "max_length": 64,
      "linked_pre_survey": {
        "id": 10,
        "survey_name": "KPT línea base"
      },
      "options_endpoint": "https://{dominio}/moontransparency/public/api/survey-show/11/household-options",
      "authenticated_options_endpoint": "https://{dominio}/moontransparency/public/api/survey/11/household-options"
    },
    "survey_link": {
      "id": 10,
      "survey_name": "KPT línea base",
      "survey_type": "PRE"
    },
    "survey_questions": [
      {
        "id": 201,
        "question_text": "ID del hogar",
        "instrument_key": "monitoring.household_id",
        "calculator_key": "household.identifier",
        "type_field": "CORTO",
        "accepts_decimals": false,
        "response_scope": "PARTICIPATION",
        "applicable_days": null,
        "scenario": null,
        "section": {
          "key": "monitoring.setup",
          "title": "Datos iniciales"
        },
        "is_required": true
      },
      {
        "id": 212,
        "question_text": "Peso de leña sobrante - cocina Moon Group",
        "type_field": "DECIMAL",
        "accepts_decimals": true,
        "response_scope": "MEASUREMENT",
        "applicable_days": [1, 2, 3, 4, 5, 6, 7],
        "scenario": "MONITORING_COMBINED",
        "section": {
          "key": "monitoring.combined",
          "title": "Caso 1: cocina tradicional + cocina mejorada Moon Group"
        }
      }
    ]
  }
}
```

**Errores:**

- `401`: token/UUID ausente o inválido.
- `403`: sin permiso en el endpoint administrativo.
- `404`: encuesta inexistente; en endpoint público también si está inactiva.

---

## VISTA PREVIA

### OBTENER VISTA PREVIA DE UNA ENCUESTA

|  |  |
|---|---|
| **Endpoint** | `/survey/{id}/preview` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` · Accept: application/json |
| **Permiso** | `surveys.view` |
| **Uso** | Botón “Vista previa” en cada fila de encuesta |

**Body:** —

**Response 200:**

```json
{
  "data": {
    "preview_mode": true,
    "read_only": true,
    "accepts_responses": false,
    "id": 10,
    "survey_name": "KPT línea base",
    "status": "ACTIVA",
    "supports_daily_measurements": true,
    "expected_days": 7,
    "survey_questions": []
  }
}
```

Frontend debe reutilizar el mismo renderer del formulario, pero deshabilitar inputs, guardado, finalización y carga de archivos.

**Errores:** `401`, `403`, `404`.

---

## HISTORIAL DE CAMBIOS

### LISTAR HISTORIAL DE UNA ENCUESTA

|  |  |
|---|---|
| **Endpoint** | `/survey/{id}/history` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` · Accept: application/json |
| **Permiso** | `surveys.view` |
| **Parámetros** | `action=CREATED|UPDATED|DELETED` · `entity_type=SURVEY|QUESTION|OPTION` · `per_page=1..100` · `page` |

**Body:** —

**Response 200:**

```json
{
  "data": [
    {
      "id": 15,
      "survey_id": 10,
      "action": "UPDATED",
      "entity_type": "QUESTION",
      "entity_id": 104,
      "description": "Se modificó una pregunta.",
      "changes": {
        "question_text": {
          "old": "Peso de leña",
          "new": "Peso inicial de leña"
        },
        "type_field": {
          "old": "NUMERICO",
          "new": "DECIMAL"
        }
      },
      "user": {
        "id": 1,
        "name": "Administrador",
        "username": "admin"
      },
      "ip_address": "192.0.2.10",
      "user_agent": "Mozilla/5.0",
      "created_at": "2026-09-24T15:30:00-05:00"
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 25,
    "total": 1
  }
}
```

Frontend debe presentar `changes` como una tabla Campo / Antes / Después y admitir valores `null`.

**Errores:**

- `403`: sin permiso.
- `404`: encuesta inexistente.
- `422`: filtro inválido.

---

## ALERTAS DEL PANEL

### LISTAR ALERTAS

|  |  |
|---|---|
| **Endpoint** | `/alerts` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` · Accept: application/json |
| **Parámetros** | `unread=true|false` · `per_page=1..100` · `page` |
| **Acceso** | Solo administrador |

**Response 200:**

```json
{
  "data": [
    {
      "id": "0c14c3b2-5be1-4ca0-9bc0-65ec4f768321",
      "type": "App\\Notifications\\SurveyParticipationChanged",
      "data": {
        "event": "SURVEY_PARTICIPATION_UPDATED",
        "action": "UPDATED",
        "message": "Juan Pérez modificó una participación de la encuesta KPT línea base.",
        "surveyed_id": 125,
        "survey_id": 10,
        "survey_name": "KPT línea base",
        "household_code": "HOG-000123",
        "actor": {
          "id": 8,
          "name": "Juan Pérez",
          "username": "jperez"
        }
      },
      "read_at": null,
      "created_at": "2026-09-24T15:35:00-05:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1,
    "unread_count": 1
  }
}
```

### OBTENER CONTADOR SIN LEER

|  |  |
|---|---|
| **Endpoint** | `/alerts/unread-count` |
| **Método** | GET |
| **Acceso** | Solo administrador |

**Response 200:**

```json
{
  "data": {
    "unread_count": 3
  }
}
```

### MARCAR UNA ALERTA COMO LEÍDA

|  |  |
|---|---|
| **Endpoint** | `/alerts/{id}/read` |
| **Método** | PATCH |
| **Acceso** | Solo administrador |

**Body:** —

Devuelve la alerta actualizada. Si no pertenece al administrador autenticado, devuelve `404`.

### MARCAR TODAS COMO LEÍDAS

|  |  |
|---|---|
| **Endpoint** | `/alerts/read-all` |
| **Método** | PATCH |
| **Acceso** | Solo administrador |

**Response 200:**

```json
{
  "data": {
    "unread_count": 0
  }
}
```

Frontend puede consultar el contador al entrar al panel y periódicamente. No se implementó WebSocket ni Server-Sent Events.

---

## SELECTOR DE HOGAR PARA MONITOREO

### BUSCAR HOGARES ELEGIBLES

|  | Admin | Público/app |
|---|---|---|
| **Endpoint** | `/survey/{survey}/household-options` | `/survey-show/{survey}/household-options` |
| **Método** | GET | GET |
| **Headers** | Bearer `{token}` | `UUID: {uuid}` |
| **Acceso** | `participations.manage` | UUID público válido |
| **Parámetros** | `search`, `surveyed_id`, `per_page`, `page` | iguales |

`survey` debe ser el ID de la encuesta POST, no el ID de la PRE.

`surveyed_id` se envía solamente al editar un borrador para que la selección actual siga apareciendo.

**Ejemplo:**

```http
GET /survey-show/11/household-options?search=HOG-0001&per_page=20
UUID: {uuid}
```

**Response 200:**

```json
{
  "data": [
    {
      "id": 44,
      "code": "HOG-000123",
      "label": "HOG-000123"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

**Errores 422:**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "survey": ["Los hogares elegibles solo se consultan para encuestas POST."]
  }
}
```

También devuelve `422` si la POST no tiene PRE vinculada o si `surveyed_id` no pertenece a esa POST.

---

## CAPTURA Y EDICIÓN DE ENCUESTAS

### CREAR BORRADOR

|  |  |
|---|---|
| **Endpoint** | `/response-survey` |
| **Método** | POST |
| **Content-Type** | `multipart/form-data` |
| **Autenticación** | No requerida |

**Body — ejemplo día 1 de monitoreo:**

```json
{
  "number_document": "74859621",
  "names": "María Quispe",
  "survey_id": 11,
  "household_code": "HOG-000123",
  "survey_variant": "MONITORING_COMBINED",
  "day_number": 1,
  "responses": [
    {
      "survey_question_id": 201,
      "response_text": "HOG-000123"
    },
    {
      "survey_question_id": 207,
      "response_text": "12,5"
    },
    {
      "survey_question_id": 212,
      "response_text": "8.25"
    }
  ]
}
```

### ACTUALIZAR UN BORRADOR O GUARDAR OTRO DÍA

|  | Público/app | Panel autenticado |
|---|---|---|
| **Endpoint** | `/response-survey/{id}` | `/surveyed/{id}` |
| **Método** | POST | PUT |
| **Content-Type** | `multipart/form-data` | `multipart/form-data` |
| **DNI** | Opcional | Opcional |

**Body — ejemplo día 2 sin repetir DNI:**

```json
{
  "names": "María Quispe",
  "survey_id": 11,
  "household_code": "HOG-000123",
  "survey_variant": "MONITORING_COMBINED",
  "day_number": 2,
  "responses": [
    {
      "survey_question_id": 211,
      "response_text": "3.5"
    },
    {
      "survey_question_id": 212,
      "response_text": "7.9"
    }
  ]
}
```

### FINALIZAR

|  |  |
|---|---|
| **Endpoint** | `/response-survey/{id}/finalize` |
| **Método** | POST |
| **Content-Type** | `multipart/form-data` |
| **DNI** | Opcional al existir la participación |

La finalización valida:

- hogar obligatorio;
- variante obligatoria para monitoreo;
- preguntas obligatorias de alcance participación;
- preguntas obligatorias de cada día aplicable;
- escenario correcto;
- valores enteros/decimales correctos;
- coordenadas, si la encuesta las exige.

**Response 200 común:**

```json
{
  "data": {
    "id": 125,
    "respondent_id": 71,
    "household_id": 44,
    "survey_id": 11,
    "survey_variant": "MONITORING_COMBINED",
    "supports_daily_measurements": true,
    "expected_days": 7,
    "status": "FINALIZADA",
    "can_edit": false,
    "completed_at": "2026-09-24T16:10:00-05:00",
    "surveyed_responses": [],
    "measurements": []
  }
}
```

**Errores relevantes:**

- `409`: participación finalizada/no editable.
- `422 household_code`: hogar repetido, inexistente en PRE finalizada o ya usado en POST.
- `422 survey_variant`: variante ausente, inválida o mezclada.
- `422 day_number`: encuesta no KPT o día fuera de `1..7`.
- `422 responses.{id}.day_{n}`: falta una respuesta obligatoria en un día.

---

## CARGAR PARTICIPACIÓN PARA EDITAR

### OBTENER PARTICIPACIÓN

|  |  |
|---|---|
| **Endpoint** | `/surveyed/{id}` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` |
| **Permiso** | `participations.view` |

La respuesta contiene:

- `respondent` y `respondent_names`;
- `household`;
- `survey_variant`;
- `status` y `can_edit`;
- respuestas globales en `surveyed_responses`;
- respuestas por día en `measurements`.

Frontend debe reconstruir el formulario con estos datos y no pedir nuevamente el DNI.

**Errores:** `401`, `403`, `404`.

---

## SINCRONIZACIÓN OFFLINE

### SINCRONIZAR LOTE

|  |  |
|---|---|
| **Endpoint** | `/offline-sync` |
| **Método** | POST |
| **Content-Type** | `application/json` o `multipart/form-data` con campo `payload` |
| **Autenticación** | No requerida |

**Body JSON:**

```json
{
  "contract_version": "1.0",
  "batch_id": "c97189bf-6713-45c6-bb09-c88f786fd7a0",
  "device_id": "field-tablet-01",
  "items": [
    {
      "client_participation_id": "2a47c2a4-e703-472d-8389-1b93c2166aed",
      "client_updated_at": "2026-09-24T16:00:00-05:00",
      "action": "SAVE_DRAFT",
      "number_document": "74859621",
      "names": "María Quispe",
      "household_code": "HOG-000123",
      "survey_variant": "MONITORING_COMBINED",
      "survey_id": 11,
      "responses": [
        {
          "survey_question_id": 201,
          "response_text": "HOG-000123"
        }
      ],
      "measurements": [
        {
          "client_measurement_id": "049376a0-cc43-4c58-b806-6df29e363eef",
          "day_number": 1,
          "responses": [
            {
              "survey_question_id": 212,
              "response_text": "8.25"
            }
          ]
        }
      ]
    }
  ]
}
```

En una edición posterior puede omitirse `number_document` si se reutiliza el mismo `client_participation_id`.

**Response 200/207:**

```json
{
  "contract_version": "1.0",
  "batch_id": "c97189bf-6713-45c6-bb09-c88f786fd7a0",
  "device_id": "field-tablet-01",
  "status": "FULL_SUCCESS",
  "replayed": false,
  "success_count": 1,
  "error_count": 0,
  "items": [
    {
      "client_participation_id": "2a47c2a4-e703-472d-8389-1b93c2166aed",
      "status": "SYNCED",
      "http_status": 200,
      "surveyed_id": 125,
      "surveyed_status": "BORRADOR",
      "can_edit": true,
      "calculator_ready": true,
      "calculator_survey_kind": "MONITORING",
      "household_id": 44,
      "household_code": "HOG-000123",
      "survey_variant": "MONITORING_COMBINED",
      "measurements": []
    }
  ]
}
```

**Reglas de reintento:**

- conservar el mismo `batch_id`;
- conservar el mismo payload y archivos;
- conservar `client_participation_id` y `client_measurement_id`;
- generar un nuevo `batch_id` solo cuando cambie el contenido.

**Códigos:**

- `200`: lote exitoso o reproducido;
- `207`: éxito parcial o errores por elemento;
- `409`: mismo `batch_id` con contenido diferente o lote en proceso;
- `422`: estructura/archivos inválidos.

---

## CONTRATO PARA LA CALCULADORA DE CO2

### OBTENER DATOS CONSOLIDADOS DE UNA PARTICIPACIÓN

|  |  |
|---|---|
| **Endpoint** | `/surveyed/{id}/calculator` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` |
| **Permiso** | `calculator.view` |

**Response 200 resumida:**

```json
{
  "data": {
    "contract": {
      "version": "1.3",
      "expected_days": 7,
      "supports_daily_measurements": true,
      "missing_value": null,
      "units": {
        "weight": "kg",
        "people": "person",
        "day": "day"
      }
    },
    "participation": {
      "id": 125,
      "survey_variant": "MONITORING_COMBINED",
      "status": "FINALIZADA",
      "data_state": "FINAL",
      "is_partial": false
    },
    "household": {
      "id": 44,
      "code": "HOG-000123",
      "identifier": "HOG-000123",
      "source": "HOUSEHOLD"
    },
    "recorded_days": [1, 2, 3, 4, 5, 6, 7],
    "missing_days": [],
    "fields": [
      {
        "key": "question_212",
        "calculator_key": "monitoring.moon.remaining_wood_kg",
        "question_id": 212,
        "value_type": "number",
        "unit": "kg",
        "required": true,
        "response_scope": "MEASUREMENT",
        "applicable_days": [1, 2, 3, 4, 5, 6, 7],
        "scenario": "MONITORING_COMBINED"
      }
    ],
    "days": [
      {
        "day_number": 1,
        "recorded": true,
        "measurement_id": 301,
        "values": {
          "question_212": {
            "question_id": 212,
            "value": 8.25,
            "raw_value": "8.25",
            "unit": "kg",
            "validation_status": "valid"
          }
        }
      }
    ],
    "calculator_input": {}
  }
}
```

Frontend debe usar `value`, `unit` y `validation_status`; no debe recalcular conversiones desde `raw_value`.

### OBTENER CONFIGURACIÓN DE CÁLCULO

|  |  |
|---|---|
| **Endpoint** | `/calculator/co2/configuration?project_id={id}` |
| **Método** | GET |
| **Headers** | Authorization: Bearer `{token}` |
| **Permiso** | `calculator.view` |

Devuelve proyecto, encuestas compatibles, cantidad de participaciones, límite de muestra y parámetros RECH predeterminados.

### EJECUTAR CÁLCULO

|  |  |
|---|---|
| **Endpoint** | `/calculator/co2` |
| **Método** | POST |
| **Content-Type** | `application/json` |
| **Permiso** | `calculator.view` |

**Body mínimo:**

```json
{
  "project_id": 2,
  "baseline_survey_id": 10,
  "monitoring_survey_id": 11,
  "household_ids": [44],
  "sample_limit": 20,
  "parameters": {
    "monitoring_year": 2026,
    "monitoring_method": "MANUAL",
    "destruction_evidence": false
  }
}
```

Los IDs `10` y `11` son solo ejemplos. Frontend debe obtenerlos desde `/calculator/co2/configuration` y usar `kind=BASELINE|MONITORING` y `post_survey_id`.

**Response 200 resumida:**

```json
{
  "data": {
    "calculation_id": 91,
    "calculated_at": "2026-09-24T16:30:00-05:00",
    "source": {
      "project_id": 2,
      "available_households": 12,
      "selected_households": 1,
      "warnings": [],
      "households": []
    },
    "calculation": {}
  }
}
```

---

## Orden recomendado de implementación frontend

1. Actualizar modelos TypeScript/Dart con los nuevos metadatos.
2. Consumir `/surveyquestion-field-types` y habilitar `DECIMAL`.
3. Implementar renderer por `response_scope`, `applicable_days`, `scenario` y `section`.
4. Implementar selector de variante y selector buscable de hogar.
5. Corregir edición para no exigir DNI otra vez.
6. Actualizar el payload online y offline.
7. Agregar vista previa e historial en Admin.
8. Agregar campana de alertas para administradores.
9. Bloquear los datos KPT en la Calculadora de CO2 y refrescarlos desde backend.
10. Quitar Inicio, renombrar Calculadora y actualizar el título del portal.

---

## Checklist de aceptación frontend

- [ ] El editor diferencia Numérico entero y Decimal.
- [ ] `12,5` puede enviarse en una pregunta DECIMAL.
- [ ] La vista previa no permite guardar ni responder.
- [ ] El historial muestra campo, valor anterior, valor nuevo, usuario y fecha.
- [ ] La campana muestra `unread_count` y permite marcar alertas.
- [ ] Solo KPT muestra siete días.
- [ ] Línea base permite escribir un ID global libre.
- [ ] Monitoreo obliga a seleccionar un hogar PRE finalizado.
- [ ] Monitoreo muestra una sola variante y no mezcla escenarios.
- [ ] Editar no vuelve a exigir DNI.
- [ ] Offline reutiliza IDs locales durante reintentos.
- [ ] La calculadora usa el mismo `household_id`/`household_code` de PRE y POST.
- [ ] Los campos KPT de la calculadora están bloqueados.
- [ ] No aparece Inicio duplicando Proyectos.
- [ ] El menú dice “Calculadora de CO2”.
- [ ] El título del portal dice “Moon Group - Portal de Impacto”.

---

## Errores comunes que frontend debe mostrar

| HTTP | Caso | Acción recomendada |
|---|---|---|
| `401` | Token o UUID inválido | Volver a autenticar/validar configuración |
| `403` | Falta permiso o no es administrador | Ocultar la acción y mostrar acceso denegado |
| `404` | Encuesta/participación/alerta inexistente | Refrescar listado |
| `409` | Participación finalizada o conflicto offline | Bloquear edición y sincronizar estado |
| `422` | Validación de formulario | Mostrar mensajes por campo entregados en `errors` |

No reemplazar los mensajes de `422` por un mensaje genérico: varios incluyen el día o la pregunta exacta que falta.
