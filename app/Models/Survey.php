<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use SoftDeletes;

    public const KIND_BASELINE = 'BASELINE';

    public const KIND_MONITORING = 'MONITORING';

    public const STATUS_ACTIVE = 'ACTIVA';

    public const STATUS_INACTIVE = 'INACTIVA';

    protected $fillable = [
        'id',
        'code',
        'proyect_id',
        'survey_name',
        'survey_type',
        'description',
        'status',
        'requires_coordinates',
        'expected_days',
        'post_survey_id',

        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'requires_coordinates' => 'boolean',
        'expected_days' => 'integer',
    ];

    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const filters = [
        'code' => '=',
        'proyect_id' => '=',
        'survey_name' => 'like',
        'description' => 'like',
        'status' => '=',
        'survey_type' => '=',
        'post_survey_id' => '=',
        'created_at' => 'between',

    ];

    public function expectedDays(): int
    {
        $days = (int) ($this->expected_days ?: config('surveying.default_expected_days', 7));

        return max(1, min($days, (int) config('surveying.max_expected_days', 31)));
    }

    public function calculatorKind(): ?string
    {
        if ($this->survey_questions()
            ->where('calculator_key', 'like', 'baseline.%')
            ->exists()) {
            return self::KIND_BASELINE;
        }

        if ($this->survey_questions()
            ->where('calculator_key', 'like', 'monitoring.%')
            ->exists()) {
            return self::KIND_MONITORING;
        }

        $name = mb_strtolower((string) $this->survey_name);

        return str_contains($name, 'linea base')
            ? self::KIND_BASELINE
            : (str_contains($name, 'monitoreo') ? self::KIND_MONITORING : null);
    }

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'survey_type' => 'desc',
    ];

    public function survey_questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function surveyeds()
    {
        return $this->hasMany(Surveyed::class);
    }

    public function proyect()
    {
        return $this->belongsTo(Proyect::class, 'proyect_id');
    }

    // Una PRE puede tener una POST
    public function postSurvey()
    {
        return $this->belongsTo(Survey::class, 'post_survey_id');
    }

    // Una POST pertenece a una PRE
    public function preSurvey()
    {
        return $this->hasOne(Survey::class, 'post_survey_id');
    }
}
