# APIs de usuarios, permisos y guardado parcial de encuestas

## URL base

```text
/moontransparency/public/api
```

Las APIs administrativas de usuarios, roles y permisos requieren autenticación:

```http
Authorization: Bearer {token}
Content-Type: application/json
```

Las APIs públicas para registrar y actualizar respuestas de encuestas no requieren token. Como pueden recibir archivos, se encuentran documentadas con `multipart/form-data`.

## 1. Usuarios y permisos

Los permisos no se asignan directamente a un usuario. La relación es:

```text
Permisos → Rol → Usuario
```

### 1.1. Listar los permisos disponibles

```http
GET /permission
```

No requiere payload.

Permisos disponibles:

| Código | Descripción |
|---|---|
| `users.view` | Leer usuarios |
| `users.create` | Crear usuarios |
| `users.update` | Editar usuarios |
| `users.delete` | Eliminar usuarios |
| `roles.view` | Leer roles |
| `roles.create` | Crear roles |
| `roles.update` | Editar roles |
| `roles.deactivate` | Desactivar roles |
| `roles.delete` | Eliminar roles |
| `roles.assign_permissions` | Asignar permisos |
| `roles.revoke_permissions` | Revocar permisos |
| `content.view` | Leer contenido |
| `content.manage` | Administrar contenido |
| `surveys.view` | Leer encuestas |
| `surveys.manage` | Administrar encuestas |
| `surveys.clean_participations` | Limpiar participaciones de encuestas |
| `participations.view` | Leer participaciones |
| `participations.manage` | Administrar participaciones |
| `participations.import` | Importar participaciones |
| `participations.export` | Exportar participaciones |
| `participations.reopen` | Reabrir participaciones |
| `respondents.view` | Leer encuestados |
| `respondents.manage` | Administrar encuestados |

Los IDs deben obtenerse de esta API; no deben fijarse en el frontend.

### 1.2. Listar roles

```http
GET /rol
```

No requiere payload.

Los roles `Administrador` y `Administrador Moon` reciben todos los permisos activos del catálogo. Los demás roles reciben únicamente los permisos que se les asignen.

### 1.3. Crear un rol

```http
POST /rol
```

Payload:

```json
{
  "name": "Encuestador",
  "status": "Activo"
}
```

Campos:

| Campo | Tipo | Obligatorio | Observación |
|---|---|---:|---|
| `name` | string | Sí | Nombre único del rol |
| `status` | string | No | `Activo` o `Inactivo` |

### 1.4. Asignar o reemplazar los permisos de un rol

La opción recomendada para guardar la selección completa del formulario es:

```http
PUT /rol/{rol_id}/setaccess
```

Payload:

```json
{
  "access": [1, 2, 14, 16]
}
```

`access` contiene los IDs retornados por `GET /permission`. Esta operación reemplaza todos los permisos actuales del rol por los enviados.

También existen estas operaciones individuales:

```http
POST /rol/{rol_id}/permissions
DELETE /rol/{rol_id}/permissions/{permission_id}
```

El `POST` para agregar permisos utiliza el mismo payload con `access`.

### 1.5. Crear un usuario

```http
POST /user
```

Payload:

```json
{
  "type_document": "DNI",
  "number_document": "74859621",
  "names": "María Quispe",
  "username": "maria.quispe",
  "password": "ClaveSegura123",
  "address": "Comunidad San José",
  "phone": "999999999",
  "email": "maria@example.com",
  "status": "Activo",
  "rol_id": 3
}
```

Campos:

| Campo | Tipo | Obligatorio | Observación |
|---|---|---:|---|
| `type_document` | string | No | Máximo 30 caracteres |
| `number_document` | string | Sí | Único, máximo 30 caracteres |
| `names` | string | Sí | Máximo 255 caracteres |
| `username` | string | Sí | Único; admite letras, números, punto, guion y guion bajo |
| `password` | string | Sí | Mínimo 8 caracteres |
| `address` | string | No | Máximo 255 caracteres |
| `phone` | string | No | Máximo 30 caracteres |
| `email` | string | No | Debe ser válido y único |
| `status` | string | No | `Activo` o `Inactivo` |
| `rol_id` | integer | Sí | ID de un rol existente |

El usuario recibe los permisos correspondientes a `rol_id`; el payload del usuario no lleva una lista de permisos.

## 2. Guardado parcial de encuestas

Una participación se crea una sola vez y permanece en estado `BORRADOR` mientras se registran o actualizan sus días.

### 2.1. Crear el borrador inicial

```http
POST /response-survey
Content-Type: multipart/form-data
```

Payload conceptual:

```json
{
  "number_document": "74859621",
  "names": "María Quispe",
  "date_of_birth": "1990-05-20",
  "phone": "999999999",
  "email": "maria@example.com",
  "genero": "Femenino",
  "household_code": "HOG-00000001",
  "survey_id": 9,
  "latitude": -6.39454,
  "longitude": -79.822403,
  "day_number": 1,
  "responses": [
    {
      "survey_question_id": 15,
      "survey_question_option_id": [3],
      "response_text": null
    },
    {
      "survey_question_id": 16,
      "survey_question_option_id": [],
      "response_text": "12.5"
    }
  ]
}
```

Campos generales:

| Campo | Tipo | Obligatorio | Observación |
|---|---|---:|---|
| `number_document` | string | Sí | Máximo 20 caracteres |
| `names` | string | Sí | Máximo 1000 caracteres |
| `date_of_birth` | date | No | Formato `YYYY-MM-DD` |
| `phone` | string | No | Máximo 255 caracteres |
| `email` | string | No | Correo válido |
| `genero` | string | No | Máximo 255 caracteres |
| `household_code` | string | No | Por ejemplo, `HOG-00000001`; debe existir |
| `survey_id` | integer | Sí | ID de la encuesta |
| `latitude` | number | No | Debe enviarse junto con `longitude` |
| `longitude` | number | No | Debe enviarse junto con `latitude` |
| `day_number` | integer | No | Desde `1` hasta `expected_days` de la encuesta |
| `responses` | array | No | Puede estar incompleto mientras sea borrador |

Campos de cada respuesta:

| Campo | Tipo | Obligatorio | Observación |
|---|---|---:|---|
| `survey_question_id` | integer | Sí | ID de una pregunta perteneciente a la encuesta |
| `survey_question_option_id` | integer[] | No | IDs de las opciones seleccionadas |
| `response_text` | string | No | Máximo 1000 caracteres |
| `file` | file | No | Máximo 5 MB |

La respuesta devuelve el `id` que debe conservar el frontend para las siguientes actualizaciones:

```json
{
  "data": {
    "id": 125,
    "status": "BORRADOR",
    "can_edit": true
  }
}
```

### 2.2. Guardar otro día o modificar el borrador

```http
POST /response-survey/{surveyed_id}
Content-Type: multipart/form-data
```

Ejemplo para el día 2:

```json
{
  "number_document": "74859621",
  "names": "María Quispe",
  "survey_id": 9,
  "day_number": 2,
  "responses": [
    {
      "survey_question_id": 15,
      "survey_question_option_id": [4],
      "response_text": null
    },
    {
      "survey_question_id": 16,
      "survey_question_option_id": [],
      "response_text": "10.8"
    }
  ]
}
```

En cada actualización son obligatorios nuevamente:

```text
number_document
names
survey_id
```

Además, `number_document` y `survey_id` deben corresponder a la participación identificada por `{surveyed_id}`.

El backend conserva los días anteriores. Si se vuelve a enviar el mismo `day_number`, actualiza las respuestas de ese día sin crear una medición duplicada.

## 3. Registro tipo “carrito” por días

No existe una API ni una entidad independiente llamada `cart` o `carrito`. El carrito es una representación de frontend: cada elemento corresponde a una medición diaria de la misma participación.

```text
Día 1 → POST /response-survey
Día 2 → POST /response-survey/{surveyed_id}
Día 3 → POST /response-survey/{surveyed_id}
...
```

Internamente, cada elemento queda identificado por la combinación:

```text
surveyed_id + day_number
```

Por eso no puede existir más de una medición del mismo día para una participación.

### Consultar los días guardados

```http
GET /surveyed/{surveyed_id}
Authorization: Bearer {token}
```

Ejemplo parcial de respuesta:

```json
{
  "data": {
    "id": 125,
    "survey_id": 9,
    "status": "BORRADOR",
    "can_edit": true,
    "measurements": [
      {
        "day_number": 1,
        "responses": []
      },
      {
        "day_number": 2,
        "responses": []
      }
    ]
  }
}
```

## 4. Finalizar la encuesta

Cuando se hayan registrado los datos requeridos:

```http
POST /response-survey/{surveyed_id}/finalize
Content-Type: multipart/form-data
```

El payload utiliza la misma estructura del guardado parcial. Al finalizar, el backend valida las preguntas obligatorias y las coordenadas requeridas.

Resultado esperado:

```json
{
  "data": {
    "id": 125,
    "status": "FINALIZADA",
    "can_edit": false,
    "completed_at": "2026-09-09T12:30:00Z"
  }
}
```

Una participación finalizada no puede modificarse hasta que un administrador la reabra.

## 5. API para la calculadora de CO₂

El guardado parcial únicamente registra las respuestas y mediciones. Para cargar la calculadora se debe consumir el endpoint consolidado:

```http
GET /surveyed/{surveyed_id}/calculator
Authorization: Bearer {token}
```

No requiere payload. `{surveyed_id}` es el ID de la participación, no el ID de la encuesta.

La respuesta usa el contrato `1.3` e incluye:

| Campo | Uso en frontend |
|---|---|
| `contract` | Versión, cantidad esperada de días y política de unidades/datos faltantes |
| `participation` | Estado `BORRADOR` o `FINALIZADA` y condición parcial/final |
| `respondent` | Identificación del encuestado |
| `household` | Identificación estable del hogar |
| `survey` | Encuesta correspondiente |
| `project` | Proyecto correspondiente |
| `recorded_days` | Días que ya tienen medición |
| `missing_days` | Días pendientes de registrar |
| `fields` | Definición y metadata de las preguntas |
| `days` | Valores normalizados de cada día |
| `calculator_input` | Datos semánticos listos para llenar la calculadora KPT |

Ejemplo resumido:

```json
{
  "data": {
    "contract": {
      "version": "1.3",
      "expected_days": 7,
      "missing_value": null,
      "units": {
        "weight": "kg",
        "people": "person",
        "day": "day"
      }
    },
    "participation": {
      "id": 125,
      "status": "BORRADOR",
      "data_state": "PARTIAL",
      "is_partial": true
    },
    "recorded_days": [1, 2],
    "missing_days": [3, 4, 5, 6, 7],
    "fields": [],
    "days": [],
    "calculator_input": {
      "version": "1.0",
      "supported": true,
      "scenario": "BASELINE",
      "household_members": {},
      "baseline": {},
      "project": null,
      "warnings": []
    }
  }
}
```

### Reglas para consumir el contrato

- El frontend debe leer `calculator_input`; no debe reconstruir el cálculo usando el texto visible de las preguntas.
- `recorded_days` y `missing_days` permiten representar el avance de los días.
- Un día faltante se entrega con `recorded=false` y valores `null`; no debe interpretarse como cero.
- Una respuesta numérica inválida conserva `raw_value`, entrega `value=null` y usa `validation_status=invalid`.
- Los pesos se entregan en kilogramos. Si la pregunta está configurada en gramos, el backend aplica el factor `0.001`.
- Las advertencias de `calculator_input.warnings` deben mostrarse al usuario.
- Un borrador puede producir resultados preliminares, pero `participation.is_partial=true` debe mostrarse claramente.

### Trabajo todavía pendiente en la calculadora

La API y el mapeo técnico ya están implementados. Aún falta:

- conectar `GET /surveyed/{id}/calculator` en el frontend administrativo;
- interpretar visualmente días registrados, faltantes, valores inválidos y advertencias;
- aprobar funcionalmente el mapeo de las encuestas KPT de línea base y monitoreo;
- confirmar la relación de la encuesta de identificación/uso con las variables `Nₚ,y` y `Uₚ,y`;
- corregir o aprobar la fórmula de `Uₚ,y`, porque la implementación actual no incorpora los porcentajes capturados;
- aprobar las fuentes oficiales de factores como NCV, factores de emisión, `fNRB`, DAF y eficiencia del equipo;
- confirmar que los cálculos con menos de todos los días son únicamente preliminares.

Por lo tanto, el backend está técnicamente listo para entregar los datos, pero la calculadora todavía necesita integración frontend y validación funcional antes de considerar oficiales sus resultados.
