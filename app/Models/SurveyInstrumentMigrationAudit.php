<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyInstrumentMigrationAudit extends Model
{
    protected $fillable = [
        'project_id',
        'baseline_survey_id',
        'monitoring_survey_id',
        'performed_by',
        'instrument_version',
        'confirmation',
        'migrated_counts',
        'warnings',
        'backup_disk',
        'backup_path',
        'backup_sha256',
    ];

    protected $casts = [
        'migrated_counts' => 'array',
        'warnings' => 'array',
    ];
}
