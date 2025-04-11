<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyedResponseOption extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'surveyed_response_id',
        'survey_question_options_id',
        'surveyed_id',
        'respondent_id',

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
        'surveyed_response_id'=> '=',
        'survey_question'=> '=',
        'surveyed_id'=> '=',
        'respondent_id'=> '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];
}
