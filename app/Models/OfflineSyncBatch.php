<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSyncBatch extends Model
{
    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'batch_id',
        'contract_version',
        'device_id',
        'request_hash',
        'status',
        'response_status',
        'response_payload',
        'user_id',
        'processed_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
