<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSyncParticipation extends Model
{
    protected $fillable = [
        'client_participation_id',
        'device_id',
        'surveyed_id',
        'last_client_updated_at',
        'last_payload_hash',
    ];

    protected $casts = [
        'last_client_updated_at' => 'datetime',
    ];

    public function surveyed()
    {
        return $this->belongsTo(Surveyed::class);
    }

    public function measurements()
    {
        return $this->hasMany(OfflineSyncMeasurement::class);
    }
}
