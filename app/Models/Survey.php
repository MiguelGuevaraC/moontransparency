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
        'description',
        'survey_type',
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
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'   => 'desc',
    ];

    public function survey_questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }
}
