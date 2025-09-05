<?php
namespace App\Http\Requests\SurveyRequest;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class UpdateSurveyRequest extends StoreRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Obtiene el survey id de la ruta/entrada de forma segura.
     * Soporta route('survey') (modelo o id), route('id') o input('id').
     */
    protected function getSurveyId()
    {
        $routeSurvey = $this->route('survey');
        if ($routeSurvey instanceof Survey) {
            return $routeSurvey->id;
        }
        if (!is_null($routeSurvey)) {
            return $routeSurvey;
        }

        $routeId = $this->route('id');
        if (!is_null($routeId)) {
            return $routeId;
        }

        return $this->input('id');
    }

    /**
     * Normaliza claves de data_ubicacion (soporta español o inglés)
     */
    protected function prepareForValidation()
    {
        $loc = $this->input('data_ubicacion');

        if (is_array($loc)) {
            $normalized = $loc;

            $map = [
                'departamento_id' => 'department_id',
                'provincia_id'    => 'province_id',
                'distrito_id'     => 'district_id',
                'comunidad'       => 'community',
            ];

            foreach ($map as $es => $en) {
                if (array_key_exists($es, $loc) && ! array_key_exists($en, $loc)) {
                    $normalized[$en] = $loc[$es];
                }
            }

            $this->merge(['data_ubicacion' => $normalized]);
        }
    }

    public function rules()
    {
        $surveyId = $this->getSurveyId();

        return [
            'proyect_id'        => 'required|integer|exists:proyects,id,deleted_at,NULL',
            'survey_name'       => [
                'required','string','max:255',
                Rule::unique('surveys','survey_name')->ignore($surveyId)->where(function ($q) {
                    return $q->where('proyect_id', $this->input('proyect_id'))->whereNull('deleted_at');
                }),
            ],
            'description'       => 'required|string|max:1000',
            'status'            => 'nullable|string|in:ACTIVA,INACTIVA',
            'survey_type'       => 'required|string|in:PRE,POST',
            'is_consentimiento' => 'nullable|boolean',

            // data_ubicacion
            'data_ubicacion'                       => 'nullable|array',
            'data_ubicacion.department_id'         => 'required_with:data_ubicacion|integer|exists:departments,id',
            'data_ubicacion.province_id'           => 'required_with:data_ubicacion|integer|exists:provinces,id',
            'data_ubicacion.district_id'           => 'required_with:data_ubicacion|integer|exists:districts,id',
            'data_ubicacion.community'             => 'required_with:data_ubicacion|string|max:255',

            // referencias PRE/POST
            'post_survey_id'    => 'nullable|integer',
            'post_survey_name'  => 'nullable|string|max:255',
            'pre_survey_id'     => 'nullable|integer',
            'pre_survey_name'   => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $surveyId = $this->getSurveyId();
            $type = $this->input('survey_type');
            $projectId = $this->input('proyect_id');

            $surveyIdInt = is_null($surveyId) ? null : intval($surveyId);

            // --- VALIDACIÓN JERÁRQUICA department -> province -> district ---
            $loc = $this->input('data_ubicacion', null);
            if (! is_null($loc) && is_array($loc)) {
                $deptId = $loc['department_id'] ?? null;
                $provId = $loc['province_id'] ?? null;
                $distId = $loc['district_id'] ?? null;

                if ($deptId && $provId) {
                    $provExists = DB::table('provinces')
                        ->where('id', $provId)
                        ->where('department_id', $deptId)
                        ->exists();

                    if (! $provExists) {
                        $v->errors()->add('data_ubicacion.province_id', 'La provincia no pertenece al departamento indicado.');
                        return;
                    }
                }

                if ($provId && $distId) {
                    $distExists = DB::table('districts')
                        ->where('id', $distId)
                        ->where('province_id', $provId)
                        ->exists();

                    if (! $distExists) {
                        $v->errors()->add('data_ubicacion.district_id', 'El distrito no pertenece a la provincia indicada.');
                        return;
                    }
                }

                if (isset($loc['community']) && trim($loc['community']) === '') {
                    $v->errors()->add('data_ubicacion.community', 'La comunidad no puede estar vacía.');
                    return;
                }
            }

            // --- Validaciones PRE/POST ---
            if ($type === 'PRE') {
                $postId = $this->input('post_survey_id');
                $postName = $this->input('post_survey_name');

                if (is_null($postId) && is_null($postName)) return;

                $post = null;
                if ($postId) {
                    $post = Survey::where('id', $postId)->where('survey_type','POST')->whereNull('deleted_at')->first();
                } else {
                    $q = Survey::where('survey_name',$postName)->where('survey_type','POST')->whereNull('deleted_at');
                    if ($this->filled('proyect_id')) $q->where('proyect_id', $projectId);
                    $post = $q->first();
                }

                if (! $post) {
                    $v->errors()->add('post_survey_id','La encuesta POST indicada no existe o no es de tipo POST.');
                    return;
                }

                $linkedPre = Survey::where('post_survey_id', $post->id)->whereNull('deleted_at')->first();
                if ($linkedPre && intval($linkedPre->id) !== $surveyIdInt) {
                    $v->errors()->add('post_survey_id',
                        "La encuesta POST '{$post->survey_name}' ya está asociada a la PRE '{$linkedPre->survey_name}'.");
                }

                return;
            }

            if ($type === 'POST') {
                $preId = $this->input('pre_survey_id');
                $preName = $this->input('pre_survey_name');

                if (is_null($preId) && is_null($preName)) {
                    $v->errors()->add('pre_survey_id','Para una encuesta POST debes indicar la PRE asociada.');
                    return;
                }

                $pre = null;
                if ($preId) {
                    $pre = Survey::where('id',$preId)->where('survey_type','PRE')->whereNull('deleted_at')->first();
                } else {
                    $q = Survey::where('survey_name',$preName)->where('survey_type','PRE')->whereNull('deleted_at');
                    if ($this->filled('proyect_id')) $q->where('proyect_id', $projectId);
                    $pre = $q->first();
                }

                if (! $pre) {
                    $v->errors()->add('pre_survey_id','La encuesta PRE indicada no existe o no es de tipo PRE.');
                    return;
                }

                if (! is_null($pre->post_survey_id) && intval($pre->post_survey_id) !== $surveyIdInt) {
                    $linkedPost = Survey::withTrashed()->find($pre->post_survey_id);
                    $v->errors()->add('pre_survey_id',
                        "La encuesta PRE '{$pre->survey_name}' ya está asociada a la POST '".($linkedPost? $linkedPost->survey_name : $pre->post_survey_id)."'.");
                }

                return;
            }
        });
    }

    public function messages()
    {
        return [
            // generales
            'survey_type.in' => 'Tipo de encuesta solo puede ser PRE o POST.',
            'data_ubicacion.array' => 'data_ubicacion debe ser un objeto con los campos: departamento, provincia, distrito y comunidad.',

            // departamento
            'data_ubicacion.department_id.required_with' => 'El campo "departamento" es obligatorio cuando se incluye data_ubicacion.',
            'data_ubicacion.department_id.integer' => 'El campo "departamento" debe ser un identificador numérico.',
            'data_ubicacion.department_id.exists' => 'El departamento indicado no existe.',

            // provincia
            'data_ubicacion.province_id.required_with' => 'El campo "provincia" es obligatorio cuando se incluye data_ubicacion.',
            'data_ubicacion.province_id.integer' => 'El campo "provincia" debe ser un identificador numérico.',
            'data_ubicacion.province_id.exists' => 'La provincia indicada no existe.',

            // distrito
            'data_ubicacion.district_id.required_with' => 'El campo "distrito" es obligatorio cuando se incluye data_ubicacion.',
            'data_ubicacion.district_id.integer' => 'El campo "distrito" debe ser un identificador numérico.',
            'data_ubicacion.district_id.exists' => 'El distrito indicado no existe.',

            // comunidad
            'data_ubicacion.community.required_with' => 'El campo "comunidad" es obligatorio cuando se incluye data_ubicacion.',
            'data_ubicacion.community.string' => 'El campo "comunidad" debe ser texto.',
            'data_ubicacion.community.max' => 'La comunidad no debe exceder los 255 caracteres.',

            // otros
            'is_consentimiento.boolean' => 'is_consentimiento debe ser booleano (true/false).',
            'survey_name.unique' => 'Ya existe una encuesta con ese nombre en el proyecto.',
        ];
    }
}
