<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestionOption extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'description',
        'survey_question_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'survey_question_id'=> '=',
        'survey_question.question_text'=> 'like',
        'survey.survey_name'=> 'like',
        'description'=> 'like',
    ];

    const sorts = [
        'id'   => 'desc',
    ];

    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class,'survey_question_id');
    }
    public function survey_question()
    {
        return $this->belongsTo(SurveyQuestion::class,'survey_question_id');
    }
    public function survey()
{
    return $this->hasOneThrough(
        Survey::class,              // Modelo destino
        SurveyQuestion::class,      // Modelo intermedio
        'id',                       // Clave en SurveyQuestion
        'id',                       // Clave en Survey
        'survey_question_id',       // Foreign key en este modelo
        'survey_id'                 // Foreign key en SurveyQuestion
    );
}

}
