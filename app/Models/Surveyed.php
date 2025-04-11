<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surveyed extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'respondent_id',
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
        'respondent_id'=> '=',
        'survey_id'=> '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'   => 'desc',
    ];

    public function surveyed_responses()
    {
        return $this->hasMany(SurveyedResponse::class);
    }
}
