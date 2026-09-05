<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surveyed extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'BORRADOR';

    protected $fillable = [
        'id',
        'respondent_id',
        'survey_id',
        'status',
   
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
        'survey.proyect_id'=> '=',
        'created_at'=> 'between',
        'survey.survey_type'=> '=',
        'status'=> '=',
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
    public function survey()
    {
        return $this->belongsTo(Survey::class,'survey_id');
    }
    public function respondent()
    {
        return $this->belongsTo(Respondent::class,'respondent_id');
    }
}
