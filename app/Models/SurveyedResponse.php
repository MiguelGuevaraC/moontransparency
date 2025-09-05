<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyedResponse extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'response_text',
        'survey_question_id',
        'surveyed_id',
        'respondent_id',
        'file_path',

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
        'response_text'   => 'like',
        'survey_question_id' => '=',
        'surveyed_id'     => '=',
        'respondent_id'  => '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];

    public function surveyed_responses_options()
    {
        return $this->hasMany(SurveyedResponseOption::class);
    }
    public function survey_question()
    {
        return $this->belongsTo(SurveyQuestion::class,'survey_question_id');
    }
}
