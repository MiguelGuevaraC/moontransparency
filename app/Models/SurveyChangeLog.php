<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyChangeLog extends Model
{
    public const ACTION_CREATED = 'CREATED';

    public const ACTION_UPDATED = 'UPDATED';

    public const ACTION_DELETED = 'DELETED';

    protected $fillable = [
        'survey_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'changes',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
