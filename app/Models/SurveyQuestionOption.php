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
        'description'=> 'like',
    ];

    const sorts = [
        'id'   => 'desc',
    ];

    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class,'survey_question_id');
    }
}
