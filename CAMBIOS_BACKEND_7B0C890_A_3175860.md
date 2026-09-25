# Moon Transparency — Cambios de backend

> Rango documentado, inclusivo: `7b0c890caa7ff430982496f25c26583f66a2095c` → `31758604853dc707bb2b0852695f623a55c61d20`.
>
> Fecha de los commits: 24 de septiembre de 2026.
>
> Actualización del 25 de septiembre de 2026: se añadió la migración segura de participaciones finalizadas para que el cambio de instrumento no dependa de que `Finalizadas` sea cero.

---

## Resumen ejecutivo

En este rango se implementó la actualización del flujo de encuestas KPT y su integración con la Calculadora de CO2:

- respuestas decimales para pesos;
- alertas administrativas por cambios realizados por encuestadores autenticados;
- datos KPT bloqueados en la calculadora cuando provienen de encuestas;
- ID de hogar global y vinculación entre la encuesta PRE y POST;
- edición de borradores sin volver a exigir el DNI;
- mediciones por días exclusivamente para KPT línea base y KPT monitoreo;
- sincronización móvil/offline compatible con la calculadora;
- vista previa de encuestas;
- historial de cambios de encuesta, preguntas y opciones;
- desactivación del menú Inicio, porque duplica Proyectos;
- sincronización segura de las dos encuestas KPT existentes con el instrumento oficial de CO2.

El rango contiene **12 commits**, **68 archivos modificados**, **4042 inserciones** y **183 eliminaciones**.

---

## Commits incluidos

| Commit | Cambio principal |
|---|---|
| `7b0c890` | Decimales y alertas de encuestas |
| `060a582` | Bloqueo de campos KPT en la calculadora |
| `926c8e4` | Actualización inicial del contrato OpenAPI |
| `c7f2fdb` | Vinculación de hogares entre PRE y POST |
| `599af1d` | Edición de participaciones y política de días KPT |
| `aa990f4` | Sincronización móvil con la calculadora |
| `7a1bca5` | Contrato OpenAPI de encuestas y sincronización |
| `d74896d` | Vista previa de encuestas |
| `e0d66e3` | Historial de cambios de encuestas |
| `06e1dc3` | Desactivación de Inicio y normalización a siete días KPT |
| `b19b68a` | Documentación OpenAPI de historial y días KPT |
| `3175860` | Sincronización de las encuestas KPT con el instrumento CO2 |

---

## 1. Respuestas decimales

Se agregó `DECIMAL` como tipo diferente de `NUMERICO`:

| Tipo | Regla de backend | Ejemplos válidos |
|---|---|---|
| `NUMERICO` | Solo enteros | `12`, `0`, `-3` |
| `DECIMAL` | Entero o decimal; acepta punto o coma | `12`, `12.5`, `12,5`, `.75` |

Los valores decimales se normalizan antes de guardarse. Por ejemplo, `012,500` se almacena como `12.5`.

También se agregó el endpoint `GET /surveyquestion-field-types` para que el editor no mantenga una lista fija de tipos.

Cuando una pregunta usa `DECIMAL` o `NUMERICO`, el backend completa `calculator_value_type=number` si el cliente no lo envía.

---

## 2. Alertas del panel administrativo

Se agregó persistencia de notificaciones y cuatro endpoints:

- `GET /alerts`
- `GET /alerts/unread-count`
- `PATCH /alerts/{id}/read`
- `PATCH /alerts/read-all`

Se generan eventos de tipo:

- `SURVEY_PARTICIPATION_CREATED`
- `SURVEY_PARTICIPATION_UPDATED`
- `SURVEY_PARTICIPATION_FINALIZED`

La alerta contiene encuesta, participación, hogar y encuestador responsable.

### Alcance de las alertas

La alerta se crea únicamente cuando:

1. el actor está autenticado;
2. el actor tiene rol de encuestador;
3. existe por lo menos un administrador activo.

Una captura completamente anónima mediante rutas públicas no permite identificar al encuestador y, por lo tanto, no genera esta alerta.

---

## 3. Calculadora protegida

Cuando la calculadora recibe datos desde encuestas:

- el ID de hogar queda en modo solo lectura;
- la composición familiar queda en modo solo lectura;
- los pesos de línea base y monitoreo quedan en modo solo lectura;
- se oculta la acción para limpiar datos de familias;
- el estado guardado en el navegador no puede reemplazar los datos recién cargados desde las encuestas.

La edición debe hacerse en la encuesta de origen y después se debe volver a consultar la calculadora.

---

## 4. ID global de hogar y vinculación PRE/POST

### KPT línea base — PRE

- El encuestador escribe libremente el ID del hogar.
- Longitud máxima: 64 caracteres.
- No se obliga al formato `HOG-00000001`; puede utilizarse cualquier código.
- La unicidad es global y no distingue mayúsculas/minúsculas.
- Al finalizar, queda disponible para la encuesta POST vinculada.

### KPT monitoreo — POST

- Solo acepta hogares pertenecientes a una línea base finalizada de la encuesta PRE vinculada.
- Un hogar ya usado por otra participación de esa misma encuesta POST deja de estar disponible.
- Al editar un borrador, `surveyed_id` permite conservar la selección actual.
- El mismo hogar se utiliza en línea base, monitoreo y calculadora.

Se agregaron dos accesos al buscador:

- autenticado: `GET /survey/{survey}/household-options`;
- público con UUID: `GET /survey-show/{survey}/household-options`.

La respuesta de detalle de encuesta incluye `household_identifier`, que indica si frontend debe mostrar texto libre o selector buscable.

---

## 5. Edición de participaciones

El DNI continúa siendo obligatorio al crear una participación nueva, pero puede omitirse al editar o finalizar una existente.

El backend conserva la persona ya vinculada. En sincronización offline también recupera el DNI desde el mapeo de `client_participation_id` cuando se trata de una edición.

Las participaciones finalizadas siguen bloqueadas y devuelven conflicto `409` hasta que un administrador las reabra.

---

## 6. Días solamente para KPT

La medición por días quedó limitada a:

- KPT línea base;
- KPT monitoreo.

Ambas utilizan exactamente siete días. Para las demás encuestas:

- `supports_daily_measurements=false`;
- `expected_days=null`;
- enviar `day_number` o `measurements` produce error `422`.

Frontend ya no debe decidir esto por nombre. Debe leer `supports_daily_measurements` y `expected_days`.

---

## 7. Sincronización móvil/offline

El contrato offline sigue en la versión `1.0`, con estas mejoras:

- el DNI puede omitirse al actualizar una participación ya mapeada;
- acepta `survey_variant` para KPT monitoreo;
- acepta el ID global de hogar;
- limita las mediciones a siete y solo para KPT;
- devuelve `calculator_ready`;
- devuelve `calculator_survey_kind` (`BASELINE` o `MONITORING`);
- devuelve `household_id`, `household_code` y `survey_variant`;
- conserva idempotencia mediante `batch_id`, `client_participation_id` y `client_measurement_id`.

---

## 8. Vista previa de encuestas

Se agregó `GET /survey/{id}/preview`.

La respuesta incluye la encuesta completa, preguntas ordenadas, opciones, ODS y metadatos de renderizado, además de:

```json
{
  "preview_mode": true,
  "read_only": true,
  "accepts_responses": false
}
```

La vista previa funciona incluso si la encuesta está inactiva, porque está destinada al panel administrativo.

---

## 9. Historial por encuesta

Se agregó auditoría automática sobre:

- encuesta (`SURVEY`);
- pregunta (`QUESTION`);
- opción de respuesta (`OPTION`).

Registra creación, actualización y eliminación, incluyendo:

- campo modificado;
- valor anterior y nuevo;
- usuario;
- IP;
- user agent;
- fecha.

El endpoint es `GET /survey/{id}/history` y admite filtros por acción y entidad.

---

## 10. Menú Inicio

La migración desactiva el registro de menú con código `home`. Proyectos queda como entrada principal.

Frontend no debe volver a agregar Inicio de manera fija. Debe respetar el estado entregado por el menú dinámico.

---

## 11. Instrumento KPT CO2 actualizado

Las encuestas existentes se modifican **en el mismo registro**, conservando sus IDs y su relación PRE → POST.

### KPT línea base

- Código: `KPT_CO2_BASELINE`.
- Tipo: `PRE`.
- 13 preguntas lógicas.
- Siete días.
- ID de hogar libre y global.
- Pesos en kilogramos con tipo `DECIMAL`.

### KPT monitoreo

- Código: `KPT_CO2_MONITORING`.
- Tipo: `POST`.
- 21 preguntas lógicas.
- Siete días.
- ID de hogar seleccionado desde la línea base vinculada.
- Dos variantes:
  - `MONITORING_COMBINED`: cocina tradicional + cocina mejorada Moon Group;
  - `MONITORING_MOON_ONLY`: solo cocina mejorada Moon Group.

Las preguntas no se duplican físicamente siete veces. Cada pregunta indica:

- `response_scope`: `PARTICIPATION` o `MEASUREMENT`;
- `applicable_days`: días en los que corresponde;
- `scenario`: variante a la que pertenece;
- `section`: agrupación visual.

---

## Migraciones agregadas

| Migración | Propósito |
|---|---|
| `2026_09_24_000001_create_notifications_table.php` | Notificaciones del panel |
| `2026_09_24_000002_assign_decimal_survey_field_type.php` | Clasificación decimal de campos de peso |
| `2026_09_24_000003_create_survey_change_logs_table.php` | Historial de encuesta/pregunta/opción |
| `2026_09_24_000004_disable_home_menu_and_normalize_kpt_days.php` | Desactivar Inicio y normalizar siete días KPT |
| `2026_09_24_000005_add_kpt_instrument_metadata.php` | Scope, días, escenario, secciones y variante de monitoreo |
| `2026_09_25_000006_create_survey_instrument_migration_audits_table.php` | Auditoría y respaldo de la migración de participaciones finalizadas |

---

## Comando de sincronización KPT

### Vista previa segura

```bash
php artisan survey:sync-kpt-co2 2
```

No modifica la base de datos. Muestra IDs, estado, cantidad actual/esperada de preguntas, borradores y finalizadas.

### Aplicación con borradores y/o participaciones finalizadas

```bash
php artisan survey:sync-kpt-co2 2 \
  --apply \
  --clean-drafts \
  --migrate-finalized \
  --actor=1 \
  --confirmation=SYNC_KPT_CO2
```

Cambiar `2` por el ID real del proyecto y `1` por el ID real de un administrador.

`--clean-drafts` solo es necesario cuando la vista previa muestra borradores. `--migrate-finalized` solo es necesario cuando muestra una o más finalizadas.

El comando:

1. respalda los borradores en `storage/app/survey-cleanup-backups/`;
2. elimina únicamente los borradores incompatibles;
3. respalda las finalizadas en `storage/app/survey-instrument-migration-backups/`;
4. conserva sus participaciones, hogares, mediciones, respuestas, archivos, estado e IDs;
5. transforma las respuestas antiguas al nuevo alcance por participación/día;
6. infiere y registra la variante de monitoreo cuando los datos lo permiten;
7. conserva en el respaldo los campos retirados del instrumento;
8. actualiza las encuestas en el mismo ID y restablece PRE → POST;
9. registra conteos, advertencias y checksum en `survey_instrument_migration_audits`;
10. puede ejecutarse nuevamente sin duplicar preguntas.

---

## Despliegue recomendado

```bash
php artisan down
php artisan migrate --force
php artisan survey:sync-kpt-co2 2
php artisan survey:sync-kpt-co2 2 --apply --clean-drafts --migrate-finalized --actor=1 --confirmation=SYNC_KPT_CO2
php artisan optimize:clear
php artisan up
```

Antes de aplicar, revisar la salida de la vista previa y confirmar que el usuario indicado en `--actor` sea administrador. Si no existen borradores o finalizadas, se pueden omitir sus opciones respectivas.

---

## APIs agregadas en el rango

| Módulo | Endpoint | Método |
|---|---|---|
| Alertas | `/alerts` | GET |
| Alertas | `/alerts/unread-count` | GET |
| Alertas | `/alerts/{id}/read` | PATCH |
| Alertas | `/alerts/read-all` | PATCH |
| Encuestas | `/survey/{id}/preview` | GET |
| Encuestas | `/survey/{id}/history` | GET |
| Encuestas | `/survey/{survey}/household-options` | GET |
| Encuestas públicas | `/survey-show/{survey}/household-options` | GET |
| Editor | `/surveyquestion-field-types` | GET |

Además, se ampliaron los contratos de detalle de encuesta, participación, sincronización offline y datos para la calculadora.

---

## Verificación

Al cerrar el rango se ejecutó la suite completa:

```text
158 tests
1314 assertions
OK
```

También se validó el estilo de los 19 archivos involucrados en la última actualización KPT.

---

## Fuera del alcance de estos commits

No se implementaron componentes visuales del SPA o de la aplicación móvil para:

- botón y modal de vista previa;
- botón y modal de historial;
- campana/listado de alertas;
- selector buscable de hogares;
- selector de variante de monitoreo;
- cambios de textos visibles como “Calculadora de CO2” y el `<title>` del portal.

El backend y sus contratos quedaron preparados para esas tareas, detalladas en `GUIA_FRONTEND_ENCUESTAS_KPT_Y_APIS.md`.
