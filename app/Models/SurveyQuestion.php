<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestion extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'question_text',
        'question_type',
        'type_field',
        'order',
        'is_required',
        'survey_id',
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
        'survey_name'   => 'like',
        'question_text' => 'like',
        'question_type' => 'like',
        'survey_id'     => '=',
        'type_field'    => '=',
        'order'         => '=',
        'is_required'   => '=',
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
}
