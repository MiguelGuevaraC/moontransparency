<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Surveyed;
use App\Models\District;
use App\Models\Province;
use App\Models\Department;

class SurveyedResponseResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="SurveyedResponse",
     *     title="SurveyedResponse",
     *     description="Modelo de respuesta de un encuestado",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="response_text", type="string", nullable=true, example="Sí, uso paneles solares."),
     *     @OA\Property(property="survey_question_id", type="integer", example=5, description="ID de la pregunta respondida"),
     *     @OA\Property(property="surveyed_id", type="integer", example=10, description="ID del registro del encuestado"),
     *     @OA\Property(property="respondent_id", type="integer", nullable=true, example=3, description="ID de la persona encuestada"),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
     * )
     */


    public function toArray($request)
    {
        $filePath = $this->file_path ?? null;

        // generar URL pública desde disk 'public' si existe path
        $fileUrl = null;
        $url = $filePath
            ? url(Storage::url($filePath))
            : null;
        if(!is_null($this->survey_question) && $this->survey_question->question_type=="UBICACION" && !empty($this->response_text)){
            $ubigeo = explode(",",$this->response_text);
            if(count($ubigeo)==3){
                $departamento = Department::where('name','like',trim($ubigeo[2]))->first();
                if(!is_null($departamento)){
                    $departamento = $departamento->id;
                    $provincia = Province::where('name','like',trim($ubigeo[1]))->where('department_id','=',$departamento)->first()->id;
                    $distrito = District::where('name','like',trim($ubigeo[0]))->where('province_id','=',$provincia)->first()->id;
                }else{
                    $provincia = null;
                    $distrito = null;
                }
            }else{
                $distrito = null;
                $provincia = null;
                $departamento = null;    
            }
        }else{
            $distrito = null;
            $provincia = null;
            $departamento = null;
        }
        return [
            'id' => $this->id ?? null,
            'response_text' => $this->response_text ?? null,
            'survey_question_id' => $this->survey_question_id ?? null,
            'survey_question_text' => $this->survey_question->question_text ?? null,
            'survey_question_type' => $this->survey_question->question_type ?? null,
            'survey_question_order' => $this->survey_question->order ?? null,
            'survey_question_type_field' => $this->survey_question->type_field ?? null,
            'surveyed_id' => $this->surveyed_id ?? null,
            'respondent_id' => $this->respondent_id ?? null,
            'file_path' => $url ?? null,
            'survey_questions_options' => $this?->survey_question?->survey_questions_options ?? null,
            'surveyed_responses_options' => $this->surveyed_responses_options ? SurveyedResponseOptionsResource::collection($this->surveyed_responses_options) : null,
            'created_at' => $this->created_at,
            'district_id' => $distrito,
            'province_id' => $provincia,
            'department_id' => $departamento,
        ];
    }



}
