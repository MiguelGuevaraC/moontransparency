<?php
namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyById(int $id): ?Survey
    {
        return Survey::find($id);
    }



    public function createSurvey(array $data): Survey
    {
        // Default status
        if (!isset($data['status'])) {
            $data['status'] = 'ACTIVA';
        }

        // --- Eliminar campos que no deben almacenarse aquí ---
        unset(
            $data['data_ubicacion'],
            $data['is_consentimiento'],
            $data['departamento_id'],
            $data['provincia_id'],
            $data['distrito_id'],
            $data['comunidad']
        );

        return DB::transaction(function () use ($data) {

            // validar survey_type básico
            $type = $data['survey_type'] ?? null;
            if (!$type) {
                throw new \Exception("El campo survey_type es obligatorio (PRE o POST).");
            }

            $projectId = $data['proyect_id'] ?? null;
            $surveyName = $data['survey_name'] ?? null;

            // Validar unicidad survey_name por proyecto (por si te saltaste la Request)
            if ($surveyName && $projectId) {
                $exists = Survey::where('survey_name', $surveyName)
                    ->where('proyect_id', $projectId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    throw new \Exception("Ya existe una encuesta con el nombre '{$surveyName}' en el proyecto.");
                }
            }

            // === Cuando se crea una POST: exige PRE y actualiza PRE.post_survey_id ===
            if (strtoupper($type) === 'POST') {
                $preIdFromInput = $data['pre_survey_id'] ?? null;
                $preNameFromInput = $data['pre_survey_name'] ?? null;

                if (is_null($preIdFromInput) && is_null($preNameFromInput)) {
                    throw new \Exception('Al crear una encuesta de tipo POST debes indicar la encuesta PRE asociada (pre_survey_id o pre_survey_name).');
                }

                // Resolver PRE y bloquearla para evitar race conditions
                $preQuery = Survey::where('survey_type', 'PRE')->whereNull('deleted_at');
                if ($preIdFromInput) {
                    $preQuery->where('id', $preIdFromInput);
                } else {
                    $preQuery->where('survey_name', $preNameFromInput);
                    if ($projectId) {
                        $preQuery->where('proyect_id', $projectId);
                    }
                }

                $pre = $preQuery->lockForUpdate()->first();

                if (!$pre) {
                    throw new \Exception('La encuesta PRE indicada no existe o no es de tipo PRE.');
                }

                // PRE debe estar libre (post_survey_id == NULL)
                if (!is_null($pre->post_survey_id)) {
                    $linkedPost = Survey::withTrashed()->find($pre->post_survey_id);
                    $linkedPostName = $linkedPost ? $linkedPost->survey_name : $pre->post_survey_id;
                    throw new \Exception("La PRE '{$pre->survey_name}' ya está asociada a la POST '{$linkedPostName}'.");
                }

                // Crear la POST (no ponemos post_survey_id en la POST)
                $post = Survey::create($data);

                // Actualizar PRE.post_survey_id = nueva POST.id
                $pre->post_survey_id = $post->id;
                $pre->save();

                return $post;
            }

            // === Cuando se crea una PRE: puede venir opcional post_survey_id/name ===
            if (strtoupper($type) === 'PRE') {
                $postIdFromInput = $data['post_survey_id'] ?? null;
                $postNameFromInput = $data['post_survey_name'] ?? null;

                if ($postIdFromInput || $postNameFromInput) {
                    // Resolver POST y bloquearla
                    $postQuery = Survey::where('survey_type', 'POST')->whereNull('deleted_at');
                    if ($postIdFromInput) {
                        $postQuery->where('id', $postIdFromInput);
                    } else {
                        $postQuery->where('survey_name', $postNameFromInput);
                        if ($projectId) {
                            $postQuery->where('proyect_id', $projectId);
                        }
                    }

                    $post = $postQuery->lockForUpdate()->first();

                    if (!$post) {
                        throw new \Exception('La encuesta POST indicada no existe o no es de tipo POST.');
                    }

                    // Verificar que ninguna PRE ya apunte a esta POST (1:1)
                    $linkedPre = Survey::where('post_survey_id', $post->id)
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if ($linkedPre) {
                        throw new \Exception("La POST '{$post->survey_name}' ya está asociada a la PRE '{$linkedPre->survey_name}'.");
                    }

                    // Asociamos la POST al crear la PRE (guardamos post_survey_id en la PRE)
                    $data['post_survey_id'] = $post->id;
                }

                // Crear PRE (con post_survey_id si vino)
                $pre = Survey::create($data);
                return $pre;
            }

            // tipo inválido
            throw new \Exception('survey_type inválido. Debe ser PRE o POST.');
        });
    }

    public function updateSurvey(Survey $proyect, array $data): Survey
    {
        // --- Eliminar campos que no deben almacenarse aquí ---
        unset(
            $data['data_ubicacion'],
            $data['is_consentimiento'],
            $data['departamento_id'],
            $data['provincia_id'],
            $data['distrito_id'],
            $data['comunidad']
        );

        return DB::transaction(function () use ($proyect, $data) {
            $surveyId = $proyect->id;
            $projectId = $data['proyect_id'] ?? $proyect->proyect_id;
            $newType = isset($data['survey_type']) ? strtoupper($data['survey_type']) : strtoupper($proyect->survey_type);
            $newName = $data['survey_name'] ?? $proyect->survey_name;

            // Validar unicidad del nombre dentro del proyecto (ignorando la propia encuesta)
            if ($newName && $projectId) {
                $exists = Survey::where('survey_name', $newName)
                    ->where('proyect_id', $projectId)
                    ->whereNull('deleted_at')
                    ->where('id', '<>', $surveyId)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Ya existe una encuesta con el nombre '{$newName}' en el proyecto.");
                }
            }

            // --- Lógica para cuando la encuesta resultante es POST ---
            if ($newType === 'POST') {
                $preIdFromInput = $data['pre_survey_id'] ?? null;
                $preNameFromInput = $data['pre_survey_name'] ?? null;

                if (is_null($preIdFromInput) && is_null($preNameFromInput)) {
                    throw new \Exception('Para una encuesta de tipo POST debe indicarse la PRE asociada (pre_survey_id o pre_survey_name).');
                }

                // Resolver PRE y bloquearla
                $preQuery = Survey::where('survey_type', 'PRE')->whereNull('deleted_at');
                if ($preIdFromInput) {
                    $preQuery->where('id', $preIdFromInput);
                } else {
                    $preQuery->where('survey_name', $preNameFromInput);
                    if ($projectId)
                        $preQuery->where('proyect_id', $projectId);
                }
                $pre = $preQuery->lockForUpdate()->first();

                if (!$pre) {
                    throw new \Exception('La encuesta PRE indicada no existe o no es de tipo PRE.');
                }

                // PRE debe estar libre o ya apuntar a esta misma POST
                if (!is_null($pre->post_survey_id) && intval($pre->post_survey_id) !== intval($surveyId)) {
                    $linkedPost = Survey::withTrashed()->find($pre->post_survey_id);
                    $linkedPostName = $linkedPost ? $linkedPost->survey_name : $pre->post_survey_id;
                    throw new \Exception("La PRE '{$pre->survey_name}' ya está asociada a la POST '{$linkedPostName}'.");
                }

                // Actualizar la encuesta (POST)
                $proyect->update($data);

                // Si otra PRE previamente apuntaba a esta POST y no es la seleccionada, limpiarla
                $oldPre = Survey::where('post_survey_id', $surveyId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($oldPre && intval($oldPre->id) !== intval($pre->id)) {
                    $oldPre->post_survey_id = null;
                    $oldPre->save();
                }

                // Asignar la PRE seleccionada a esta POST (si aún no está)
                if (intval($pre->post_survey_id) !== intval($surveyId)) {
                    $pre->post_survey_id = $surveyId;
                    $pre->save();
                }

                // Recargar y devolver
                $proyect->refresh();
                return $proyect;
            }

            // --- Lógica para cuando la encuesta resultante es PRE ---
            if ($newType === 'PRE') {
                // Si nos pasan post_survey_id o post_survey_name, resolver y validar
                $postIdFromInput = $data['post_survey_id'] ?? null;
                $postNameFromInput = $data['post_survey_name'] ?? null;

                if ($postIdFromInput || $postNameFromInput) {
                    $postQuery = Survey::where('survey_type', 'POST')->whereNull('deleted_at');
                    if ($postIdFromInput) {
                        $postQuery->where('id', $postIdFromInput);
                    } else {
                        $postQuery->where('survey_name', $postNameFromInput);
                        if ($projectId)
                            $postQuery->where('proyect_id', $projectId);
                    }

                    $post = $postQuery->lockForUpdate()->first();

                    if (!$post) {
                        throw new \Exception('La encuesta POST indicada no existe o no es de tipo POST.');
                    }

                    // Verificar que ninguna otra PRE distinta a la actual ya apunte a esta POST
                    $linkedPre = Survey::where('post_survey_id', $post->id)
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if ($linkedPre && intval($linkedPre->id) !== intval($surveyId)) {
                        throw new \Exception("La POST '{$post->survey_name}' ya está asociada a la PRE '{$linkedPre->survey_name}'.");
                    }

                    // Asegurar que el campo que vamos a guardar sea el id de la POST
                    $data['post_survey_id'] = $post->id;
                }

                // Actualizamos la PRE con los datos (se permite quitar post_survey_id si viene explícito)
                $proyect->update($data);

                $proyect->refresh();
                return $proyect;
            }

            throw new \Exception('survey_type inválido. Debe ser PRE o POST.');
        });
    }



    public function destroyById($id)
    {
        return Survey::find($id)?->delete() ?? false;
    }

}
