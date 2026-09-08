<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyedReopening extends Model
{
    protected $fillable = [
        'surveyed_id',
        'previous_status',
        'previous_completed_at',
        'reason',
        'reopened_by',
    ];

    protected $casts = [
        'previous_completed_at' => 'datetime',
    ];

    public function surveyed()
    {
        return $this->belongsTo(Surveyed::class);
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
