<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestion extends Model
{
    use SoftDeletes;

    public const FIELD_TYPE_DECIMAL = 'DECIMAL';

    public const RESPONSE_SCOPE_PARTICIPATION = 'PARTICIPATION';

    public const RESPONSE_SCOPE_MEASUREMENT = 'MEASUREMENT';

    public const INTEGER_FIELD_TYPES = ['NUMERICO', 'NUMERO', 'NUMBER'];

    public const FIELD_TYPE_OPTIONS = [
        ['value' => 'NUMERICO', 'label' => 'Numérico (entero)'],
        ['value' => self::FIELD_TYPE_DECIMAL, 'label' => 'Decimal'],
        ['value' => 'FECHA', 'label' => 'Fecha'],
        ['value' => 'LARGO', 'label' => 'Párrafo largo'],
        ['value' => 'CORTO', 'label' => 'Párrafo corto'],
    ];

    protected $fillable = [
        'id',
        'question_text',
        'instrument_key',
        'calculator_key',
        'calculator_value_type',
        'calculator_unit',
        'response_scope',
        'applicable_days',
        'scenario',
        'section_key',
        'section_title',
        'question_type',
        'type_field',
        'order',
        'eje',
        'justification',
        'is_required',
        'survey_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'applicable_days' => 'array',
        'is_required' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const filters = [
        'survey_name' => 'like',
        'question_text' => 'like',
        'calculator_key' => '=',
        'question_type' => 'like',
        'survey_id' => '=',
        'type_field' => '=',
        'order' => '=',
        'is_required' => '=',
        'eje' => '=',
        'justification' => 'like',
    ];

    const sorts = [
        'id' => 'desc',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function survey_questions_options()
    {
        return $this->hasMany(SurveyQuestionOption::class);
    }

    public function ods()
    {
        return $this->belongsToMany(
            Ods::class,                 // Modelo destino
            'survey_question_ods',      // Tabla pivote
            'survey_question_id',       // FK en la pivote hacia SurveyQuestion
            'ods_id'                    // FK en la pivote hacia Ods
        )
            ->withPivot('id')
            ->wherePivotNull('deleted_at'); // Excluye registros eliminados lógicamente
    }

    public function surveyed_responses()
    {
        return $this->hasMany(SurveyedResponse::class, 'survey_question_id');
    }

    public function effectiveResponseScope(): string
    {
        if (in_array($this->response_scope, [
            self::RESPONSE_SCOPE_PARTICIPATION,
            self::RESPONSE_SCOPE_MEASUREMENT,
        ], true)) {
            return $this->response_scope;
        }

        if ($this->calculator_key === 'household.identifier') {
            return self::RESPONSE_SCOPE_PARTICIPATION;
        }

        return $this->survey?->supportsDailyMeasurements()
            ? self::RESPONSE_SCOPE_MEASUREMENT
            : self::RESPONSE_SCOPE_PARTICIPATION;
    }

    public function appliesToDay(int $day): bool
    {
        if ($this->applicable_days === null || $this->applicable_days === []) {
            return true;
        }

        return in_array($day, array_map('intval', $this->applicable_days), true);
    }
}
