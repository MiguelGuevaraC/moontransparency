<?php

namespace App\Observers;

use App\Models\Survey;
use App\Models\SurveyChangeLog;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SurveyChangeObserver
{
    private const IGNORED_FIELDS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function created(Model $model): void
    {
        $changes = collect($this->auditableAttributes($model))
            ->map(fn ($value) => ['old' => null, 'new' => $this->normalize($value)])
            ->all();

        $this->record($model, SurveyChangeLog::ACTION_CREATED, $changes);
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(self::IGNORED_FIELDS)
            ->mapWithKeys(fn ($value, $field) => [$field => [
                'old' => $this->normalize($model->getRawOriginal($field)),
                'new' => $this->normalize($value),
            ]])
            ->all();

        if ($changes !== []) {
            $this->record($model, SurveyChangeLog::ACTION_UPDATED, $changes);
        }
    }

    public function deleting(Model $model): void
    {
        $changes = collect($this->auditableAttributes($model))
            ->map(fn ($value) => ['old' => $this->normalize($value), 'new' => null])
            ->all();

        $this->record($model, SurveyChangeLog::ACTION_DELETED, $changes);
    }

    private function record(Model $model, string $action, array $changes): void
    {
        $surveyId = $this->surveyId($model);
        if (! $surveyId || $changes === []) {
            return;
        }

        SurveyChangeLog::create([
            'survey_id' => $surveyId,
            'action' => $action,
            'entity_type' => $this->entityType($model),
            'entity_id' => $model->getKey(),
            'description' => $this->description($model, $action),
            'changes' => $changes,
            'user_id' => Auth::id(),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    private function surveyId(Model $model): ?int
    {
        if ($model instanceof Survey) {
            return (int) $model->id;
        }

        if ($model instanceof SurveyQuestion) {
            return $model->survey_id ? (int) $model->survey_id : null;
        }

        if ($model instanceof SurveyQuestionOption) {
            $surveyId = SurveyQuestion::withTrashed()
                ->whereKey($model->survey_question_id)
                ->value('survey_id');

            return $surveyId ? (int) $surveyId : null;
        }

        return null;
    }

    private function entityType(Model $model): string
    {
        return match (true) {
            $model instanceof Survey => 'SURVEY',
            $model instanceof SurveyQuestion => 'QUESTION',
            $model instanceof SurveyQuestionOption => 'OPTION',
            default => 'UNKNOWN',
        };
    }

    private function description(Model $model, string $action): string
    {
        $entity = match (true) {
            $model instanceof Survey => 'la encuesta',
            $model instanceof SurveyQuestion => 'una pregunta',
            $model instanceof SurveyQuestionOption => 'una opción de respuesta',
            default => 'un elemento',
        };
        $verb = match ($action) {
            SurveyChangeLog::ACTION_CREATED => 'Se creó',
            SurveyChangeLog::ACTION_DELETED => 'Se eliminó',
            default => 'Se modificó',
        };

        return "$verb $entity.";
    }

    private function auditableAttributes(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(self::IGNORED_FIELDS)
            ->all();
    }

    private function normalize($value)
    {
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}
