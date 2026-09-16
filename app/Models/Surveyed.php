<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surveyed extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'BORRADOR';

    public const STATUS_FINALIZED = 'FINALIZADA';

    protected $fillable = [
        'id',
        'respondent_id',
        'household_id',
        'survey_id',
        'status',
        'completed_at',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',

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
        'id' => '=',
        'respondent_id' => '=',
        'survey_id' => '=',
        'created_by' => '=',
        'survey.proyect_id' => '=',
        'created_at' => 'between',
        'survey.survey_type' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];

    public function surveyed_responses()
    {
        return $this->hasMany(SurveyedResponse::class);
    }

    public function measurements()
    {
        return $this->hasMany(SurveyedMeasurement::class)->orderBy('day_number');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function respondent()
    {
        return $this->belongsTo(Respondent::class, 'respondent_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reopenings()
    {
        return $this->hasMany(SurveyedReopening::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isSurveyor()) {
            $query->where($query->qualifyColumn('created_by'), $user->id);
        }

        return $query;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null
            && $this->created_by !== null
            && (int) $this->created_by === (int) $user->id;
    }

    public function isVisibleTo(?User $user): bool
    {
        return ! $user?->isSurveyor() || $this->isOwnedBy($user);
    }

    public function isEditableBy(?User $user): bool
    {
        if ($user === null) {
            return $this->created_by === null;
        }

        if ($user->isAdministrator() || $user->isSupervisor()) {
            return true;
        }

        return $this->isOwnedBy($user);
    }
}
