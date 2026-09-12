<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Co2Calculation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'baseline_survey_id',
        'monitoring_survey_id',
        'executed_by',
        'household_ids',
        'parameters',
        'result',
        'methodology',
        'formula_version',
        'contract_version',
    ];

    protected $casts = [
        'household_ids' => 'array',
        'parameters' => 'array',
        'result' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Proyect::class, 'project_id');
    }

    public function baselineSurvey()
    {
        return $this->belongsTo(Survey::class, 'baseline_survey_id');
    }

    public function monitoringSurvey()
    {
        return $this->belongsTo(Survey::class, 'monitoring_survey_id');
    }

    public function executedBy()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
