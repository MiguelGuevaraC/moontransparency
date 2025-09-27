<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyQuestionOdsRequest\IndexSurveyQuestionOdsRequest;
use App\Http\Requests\SurveyQuestionOdsRequest\StoreSurveyQuestionOdsRequest;
use App\Http\Requests\SurveyQuestionOdsRequest\UpdateSurveyQuestionOdsRequest;
use App\Http\Resources\SurveyQuestionOdsResource;
use App\Models\LogsError;
use App\Models\Survey;
use App\Models\SurveyQuestionOds;
use App\Services\SurveyQuestionOdsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SurveyQuestionOdsController extends Controller
{
    private $object_name = 'ODS de Pregunta de Encuesta';

    public function __construct(protected SurveyQuestionOdsService $service)
    {
    }

    public function list(IndexSurveyQuestionOdsRequest $request)
    {
        return $this->getFilteredResults(SurveyQuestionOds::class, $request, SurveyQuestionOds::filters, SurveyQuestionOds::sorts, SurveyQuestionOdsResource::class);
    }

    public function show($id)
    {
        $item = $this->getModelOrFail($id, false);
        return new SurveyQuestionOdsResource($item);
    }

    public function store(StoreSurveyQuestionOdsRequest $request)
    {
        return $this->handleService(
            fn() => $this->service->createSurveyQuestionOds($request->validated()),
            $request->validated(),
            "'{$this->object_name}' creado exitosamente",
            201,
            "store_SurveyQuestionOds_error",
            SurveyQuestionOdsResource::class
        );
    }

    public function update(UpdateSurveyQuestionOdsRequest $request, $id)
    {
        $item = $this->getModelOrFail($id);
        return $this->handleService(
            fn() => $this->service->updateSurveyQuestionOds($item, $request->validated()),
            $request->validated(),
            "'{$this->object_name}' actualizado exitosamente",
            200,
            "update_SurveyQuestionOds_error",
            SurveyQuestionOdsResource::class
        );
    }

    public function destroy($id)
    {
        $this->getModelOrFail($id);
        return $this->handleService(
            fn() => $this->service->destroyById($id),
            ['id' => $id],
            "'{$this->object_name}' eliminado exitosamente",
            200,
            "delete_SurveyQuestionOds_error"
        );
    }

    protected function getModelOrFail($id, bool $checkBlocked = true): SurveyQuestionOds
    {
        $item = $this->service->getSurveyQuestionOdsById($id);

        if (!$item) {
            abort(response()->json(['message' => "'{$this->object_name}' no encontrado"], 404));
        }

        if ($checkBlocked) {
            collect([])->contains($item->id) &&
                abort(response()->json([
                    'message' => "El '{$this->object_name}' '{$item->name}' no puede ser modificado/eliminado"
                ], 422));
        }

        return $item;
    }

    public function chartsByOds(Request $request, $surveyId)
    {
        $odsIds = $request->input('ods_ids', []);

        return $this->handleService(
            fn() => $this->service->getSurveyChartsByOds($surveyId, $odsIds),
            ['survey_id' => $surveyId, 'ods_ids' => $odsIds],
            "'{$this->object_name}' obtenido exitosamente",
            200,
            "charts_by_ods_error"
        );
    }
}