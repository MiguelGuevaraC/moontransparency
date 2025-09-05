<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'proyect_id',
        'survey_name',
        'survey_type',
        'description',
        'status',
        'post_survey_id',

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
        'proyect_id' => '=',
        'survey_name' => 'like',
        'description' => 'like',
        'status' => '=',
        'survey_type' => '=',
        'post_survey_id' => '=',
        'created_at' => 'between',

    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];

    public function survey_questions()
    {
        return $this->hasMany(SurveyQuestion::class);
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
