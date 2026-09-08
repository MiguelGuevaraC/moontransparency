<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyCleanupAudit extends Model
{
    protected $fillable = [
        'survey_id',
        'performed_by',
        'reason',
        'confirmation',
        'deleted_counts',
        'backup_disk',
        'backup_path',
        'backup_sha256',
    ];

    protected $casts = [
        'deleted_counts' => 'array',
    ];

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
