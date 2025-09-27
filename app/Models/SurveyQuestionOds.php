<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestionOds extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'survey_question_id', 'ods_id', 'created_at'
    ];

    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    const filters = [
        'survey_question_id' => 'like',
        'ods_id' => 'like',
        'created_at' => 'between'
    ];

    const sorts = [
        'id' => 'desc'
    ];

    
    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function ods()
    {
        return $this->belongsTo(Ods::class, 'ods_id');
    }
}