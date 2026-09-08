<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSyncMeasurement extends Model
{
    protected $fillable = [
        'offline_sync_participation_id',
        'client_measurement_id',
        'surveyed_measurement_id',
        'day_number',
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];
}
