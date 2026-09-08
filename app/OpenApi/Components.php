<?php

namespace App\OpenApi;

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     required={"message"},
 *
 *     @OA\Property(property="message", type="string", example="El recurso solicitado no existe.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     required={"message"},
 *
 *     @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\AdditionalProperties(type="array", @OA\Items(type="string"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SurveyAnswerInput",
 *     type="object",
 *     required={"survey_question_id"},
 *
 *     @OA\Property(property="survey_question_id", type="integer", minimum=1, example=15),
 *     @OA\Property(property="survey_question_option_id", type="array", nullable=true, @OA\Items(type="integer"), example={3}),
 *     @OA\Property(property="response_text", type="string", nullable=true, maxLength=1000, example="12.5"),
 *     @OA\Property(property="file", type="string", format="binary", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SurveyedUpsertRequest",
 *     type="object",
 *     required={"number_document", "names", "survey_id"},
 *
 *     @OA\Property(property="number_document", type="string", maxLength=20, example="74859621"),
 *     @OA\Property(property="names", type="string", maxLength=1000, example="María Quispe"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true, maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="genero", type="string", nullable=true),
 *     @OA\Property(property="household_code", type="string", nullable=true, pattern="^HOG-[0-9]{8,}$", example="HOG-00000001"),
 *     @OA\Property(property="survey_id", type="integer", minimum=1, example=9),
 *     @OA\Property(property="latitude", type="number", format="double", nullable=true, minimum=-90, maximum=90, example=-6.39454),
 *     @OA\Property(property="longitude", type="number", format="double", nullable=true, minimum=-180, maximum=180, example=-79.822403),
 *     @OA\Property(property="day_number", type="integer", nullable=true, minimum=1, maximum=7, example=1),
 *     @OA\Property(property="responses", type="array", @OA\Items(ref="#/components/schemas/SurveyAnswerInput"))
 * )
 *
 * @OA\Schema(
 *     schema="GeobosquesMap",
 *     type="object",
 *     required={"available", "provider", "marker_supported", "requires_connection", "load_strategy"},
 *
 *     @OA\Property(property="available", type="boolean", example=true),
 *     @OA\Property(property="provider", type="string", example="GEOBOSQUES_MINAM"),
 *     @OA\Property(property="latitude", type="number", format="double", nullable=true, minimum=-90, maximum=90),
 *     @OA\Property(property="longitude", type="number", format="double", nullable=true, minimum=-180, maximum=180),
 *     @OA\Property(property="viewer_url", type="string", format="uri", nullable=true),
 *     @OA\Property(property="embed_url", type="string", format="uri", nullable=true),
 *     @OA\Property(property="marker_supported", type="boolean", example=true),
 *     @OA\Property(property="marker_parameter", type="string", example="xy"),
 *     @OA\Property(property="requires_connection", type="boolean", example=true),
 *     @OA\Property(property="load_strategy", type="string", enum={"WHEN_ONLINE"}),
 *     @OA\Property(property="message", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SurveyedMeasurement",
 *     type="object",
 *     required={"id", "surveyed_id", "day_number", "responses"},
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="surveyed_id", type="integer"),
 *     @OA\Property(property="day_number", type="integer", minimum=1, maximum=7),
 *     @OA\Property(property="responses", type="array", @OA\Items(ref="#/components/schemas/SurveyedResponse")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Surveyed",
 *     type="object",
 *     required={"id", "survey_id", "status", "can_edit", "geobosques_map", "measurements"},
 *
 *     @OA\Property(property="id", type="integer", example=125),
 *     @OA\Property(property="respondent_id", type="integer", nullable=true),
 *     @OA\Property(property="respondent_names", type="string", nullable=true),
 *     @OA\Property(property="household_id", type="integer", nullable=true),
 *     @OA\Property(property="survey_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"BORRADOR", "FINALIZADA"}),
 *     @OA\Property(property="can_edit", type="boolean", description="Solo es true para participaciones en BORRADOR."),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="double", nullable=true, minimum=-90, maximum=90),
 *     @OA\Property(property="longitude", type="number", format="double", nullable=true, minimum=-180, maximum=180),
 *     @OA\Property(property="geobosques_map", ref="#/components/schemas/GeobosquesMap"),
 *     @OA\Property(property="surveyed_responses", type="array", @OA\Items(ref="#/components/schemas/SurveyedResponse")),
 *     @OA\Property(property="measurements", type="array", @OA\Items(ref="#/components/schemas/SurveyedMeasurement")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="UserInput",
 *     type="object",
 *     required={"number_document", "names", "username", "password", "rol_id"},
 *
 *     @OA\Property(property="type_document", type="string", nullable=true, maxLength=30),
 *     @OA\Property(property="number_document", type="string", maxLength=30),
 *     @OA\Property(property="names", type="string", maxLength=255),
 *     @OA\Property(property="username", type="string", pattern="^[A-Za-z0-9._-]+$"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, writeOnly=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"}),
 *     @OA\Property(property="rol_id", type="integer", minimum=1)
 * )
 *
 * @OA\Schema(
 *     schema="UserUpdateInput",
 *     type="object",
 *
 *     @OA\Property(property="type_document", type="string", nullable=true, maxLength=30),
 *     @OA\Property(property="number_document", type="string", maxLength=30),
 *     @OA\Property(property="names", type="string", maxLength=255),
 *     @OA\Property(property="username", type="string", pattern="^[A-Za-z0-9._-]+$"),
 *     @OA\Property(property="password", type="string", format="password", nullable=true, minLength=8, writeOnly=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"}),
 *     @OA\Property(property="rol_id", type="integer", minimum=1)
 * )
 *
 * @OA\Schema(
 *     schema="RoleInput",
 *     type="object",
 *     required={"name"},
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Supervisor"),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"})
 * )
 *
 * @OA\Schema(
 *     schema="RoleAccessInput",
 *     type="object",
 *     required={"access"},
 *
 *     @OA\Property(property="access", type="array", uniqueItems=true, @OA\Items(type="integer"), example={1, 2, 3})
 * )
 */
final class Components
{
}
