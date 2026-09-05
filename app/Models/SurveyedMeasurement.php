<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyedMeasurement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'surveyed_id',
        'day_number',
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];

    public function surveyed()
    {
        return $this->belongsTo(Surveyed::class);
    }

    public function surveyed_responses()
    {
        return $this->hasMany(SurveyedResponse::class);
    }
}
