<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyInstrumentMigrationAudit;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class KptCo2FinalizedMigrationService
{
    private const BACKUP_DISK = 'local';

    public function migrate(
        Proyect $project,
        User $actor,
        KptCo2SurveyConfigurator $configurator
    ): array {
        $legacySurveys = collect($configurator->findConfiguredSurveys($project));
        $snapshot = $this->snapshot($project, $legacySurveys);
        $backup = $this->storeBackup($project, $snapshot);

        return DB::transaction(function () use ($project, $actor, $configurator, $backup) {
            $surveys = $configurator->configure($project, true);
            $counts = $this->emptyCounts();
            $warnings = [];

            $this->migrateSurvey($surveys['baseline'], Survey::KIND_BASELINE, $counts, $warnings);
            $this->migrateSurvey($surveys['monitoring'], Survey::KIND_MONITORING, $counts, $warnings);

            $audit = SurveyInstrumentMigrationAudit::create([
                'project_id' => $project->id,
                'baseline_survey_id' => $surveys['baseline']->id,
                'monitoring_survey_id' => $surveys['monitoring']->id,
                'performed_by' => $actor->id,
                'instrument_version' => config('kpt_co2.version'),
                'confirmation' => config('kpt_co2.confirmation'),
                'migrated_counts' => $counts,
                'warnings' => $warnings,
                'backup_disk' => self::BACKUP_DISK,
                'backup_path' => $backup['path'],
                'backup_sha256' => $backup['sha256'],
            ]);

            return compact('surveys', 'audit', 'counts', 'warnings');
        }, 3);
    }

    private function migrateSurvey(
        Survey $survey,
        string $kind,
        array &$counts,
        array &$warnings
    ): void {
        $questions = $survey->survey_questions()
            ->with('survey_questions_options')
            ->get()
            ->keyBy('id');
        $participations = Surveyed::query()
            ->where('survey_id', $survey->id)
            ->where(function ($query) {
                $query->where('status', Surveyed::STATUS_FINALIZED)
                    ->orWhereNotNull('completed_at');
            })
            ->with([
                'surveyed_responses.measurement',
                'surveyed_responses.surveyed_responses_options',
                'measurements',
            ])
            ->lockForUpdate()
            ->get();

        foreach ($participations as $participation) {
            $counts['finalized_participations']++;
            $responses = $participation->surveyed_responses;

            if ($kind === Survey::KIND_MONITORING) {
                $this->inferMonitoringVariant($participation, $questions, $responses, $counts, $warnings);
            }

            $this->archiveObsoleteResponses($participation, $questions, $responses, $counts);
            $responses = $this->activeResponses($participation);
            $this->normalizeResponseScopes($participation, $questions, $responses, $counts, $warnings);
            $responses = $this->activeResponses($participation);
            $this->linkHousehold($participation, $questions, $responses, $counts, $warnings);
        }
    }

    private function inferMonitoringVariant(
        Surveyed $participation,
        Collection $questions,
        Collection $responses,
        array &$counts,
        array &$warnings
    ): void {
        $variants = array_keys(config('kpt_co2.variants', []));
        if (in_array($participation->survey_variant, $variants, true)) {
            return;
        }

        $answeredByScenario = collect($variants)->mapWithKeys(function (string $variant) use ($questions, $responses) {
            $questionIds = $questions->where('scenario', $variant)->keys();
            $answered = $responses
                ->whereIn('survey_question_id', $questionIds)
                ->filter(fn (SurveyedResponse $response) => $this->hasValue($response));

            return [$variant => $answered];
        });
        $combined = $answeredByScenario->get('MONITORING_COMBINED', collect());
        $moonOnly = $answeredByScenario->get('MONITORING_MOON_ONLY', collect());
        $variant = null;

        if ($combined->isNotEmpty() && $moonOnly->isEmpty()) {
            $variant = 'MONITORING_COMBINED';
        } elseif ($moonOnly->isNotEmpty() && $combined->isEmpty()) {
            $variant = 'MONITORING_MOON_ONLY';
        } elseif ($combined->isNotEmpty() && $moonOnly->isNotEmpty()) {
            $traditionalIds = $questions
                ->filter(fn (SurveyQuestion $question) => str_starts_with(
                    (string) $question->calculator_key,
                    'monitoring.traditional.'
                ))
                ->keys();
            $hasTraditionalData = $combined->contains(
                fn (SurveyedResponse $response) => $traditionalIds->contains($response->survey_question_id)
            );
            $variant = $hasTraditionalData || $combined->count() >= $moonOnly->count()
                ? 'MONITORING_COMBINED'
                : 'MONITORING_MOON_ONLY';
            $counts['ambiguous_variants']++;
            $warnings[] = sprintf(
                'Participación %d contenía respuestas de ambos casos; se conservó %s como variante activa y el respaldo mantiene todos los valores originales.',
                $participation->id,
                $variant
            );
        }

        if ($variant === null) {
            $counts['unresolved_variants']++;
            $warnings[] = "No se pudo inferir la variante de monitoreo de la participación {$participation->id}.";

            return;
        }

        $participation->update(['survey_variant' => $variant]);
        $counts['variants_inferred']++;
    }

    private function archiveObsoleteResponses(
        Surveyed $participation,
        Collection $questions,
        Collection $responses,
        array &$counts
    ): void {
        foreach ($responses as $response) {
            if ($questions->has($response->survey_question_id)) {
                continue;
            }

            $this->archiveResponse($response);
            $counts['obsolete_responses_archived']++;
        }
    }

    private function normalizeResponseScopes(
        Surveyed $participation,
        Collection $questions,
        Collection $responses,
        array &$counts,
        array &$warnings
    ): void {
        foreach ($questions as $question) {
            $questionResponses = $responses->where('survey_question_id', $question->id)->values();
            if ($questionResponses->isEmpty()) {
                continue;
            }

            if ($question->effectiveResponseScope() === SurveyQuestion::RESPONSE_SCOPE_PARTICIPATION) {
                $this->collapseParticipationResponses(
                    $participation,
                    $question,
                    $questionResponses,
                    $counts,
                    $warnings
                );

                continue;
            }

            foreach ($questionResponses as $response) {
                if ($response->surveyed_measurement_id === null) {
                    $day = $question->applicable_days === [1]
                        ? 1
                        : (int) ($participation->measurements->first()?->day_number ?? 1);
                    $measurement = $this->measurement($participation, $day);
                    $response->update(['surveyed_measurement_id' => $measurement->id]);
                    $response->setRelation('measurement', $measurement);
                    $counts['responses_moved_to_measurement']++;
                }

                $day = (int) $response->measurement?->day_number;
                if (! $question->appliesToDay($day)) {
                    $this->archiveResponse($response);
                    $counts['inapplicable_responses_archived']++;
                }
            }

            $this->archiveDuplicateMeasurementResponses($participation, $question, $counts, $warnings);
        }
    }

    private function collapseParticipationResponses(
        Surveyed $participation,
        SurveyQuestion $question,
        Collection $responses,
        array &$counts,
        array &$warnings
    ): void {
        $canonical = $responses->sortBy(fn (SurveyedResponse $response) => [
            $this->hasValue($response) ? 0 : 1,
            $response->surveyed_measurement_id === null ? 0 : 1,
            (int) ($response->measurement?->day_number ?? PHP_INT_MAX),
            $response->id,
        ])->first();
        $values = $responses
            ->filter(fn (SurveyedResponse $response) => $this->hasValue($response))
            ->map(fn (SurveyedResponse $response) => trim((string) $response->response_text))
            ->unique()
            ->values();

        if ($values->count() > 1) {
            $warnings[] = sprintf(
                'Participación %d, pregunta %d: había valores diarios distintos; se conservó "%s" y el respaldo contiene todos.',
                $participation->id,
                $question->id,
                (string) $canonical->response_text
            );
        }

        if ($canonical->surveyed_measurement_id !== null) {
            $canonical->update(['surveyed_measurement_id' => null]);
            $counts['responses_moved_to_participation']++;
        }

        foreach ($responses->where('id', '<>', $canonical->id) as $duplicate) {
            $this->archiveResponse($duplicate);
            $counts['duplicate_responses_archived']++;
        }
    }

    private function archiveDuplicateMeasurementResponses(
        Surveyed $participation,
        SurveyQuestion $question,
        array &$counts,
        array &$warnings
    ): void {
        $responses = $this->activeResponses($participation)
            ->where('survey_question_id', $question->id)
            ->groupBy('surveyed_measurement_id');

        foreach ($responses as $measurementId => $duplicates) {
            if ($duplicates->count() < 2) {
                continue;
            }

            $canonical = $duplicates->sortBy(fn (SurveyedResponse $response) => [
                $this->hasValue($response) ? 0 : 1,
                $response->id,
            ])->first();
            foreach ($duplicates->where('id', '<>', $canonical->id) as $duplicate) {
                $this->archiveResponse($duplicate);
                $counts['duplicate_responses_archived']++;
            }
            $warnings[] = sprintf(
                'Participación %d, pregunta %d, medición %s: se archivaron respuestas duplicadas.',
                $participation->id,
                $question->id,
                $measurementId
            );
        }
    }

    private function linkHousehold(
        Surveyed $participation,
        Collection $questions,
        Collection $responses,
        array &$counts,
        array &$warnings
    ): void {
        $question = $questions->firstWhere('calculator_key', 'household.identifier');
        $response = $question
            ? $responses->firstWhere('survey_question_id', $question->id)
            : null;
        $code = trim((string) ($response?->response_text ?? ''));

        if ($code === '') {
            $counts['households_unresolved']++;
            $warnings[] = "La participación {$participation->id} no tiene ID de hogar para vincular.";

            return;
        }

        $household = Household::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->lockForUpdate()
            ->first();
        if (! $household) {
            $household = Household::create(['code' => $code]);
            $counts['households_created']++;
        }

        if ((int) $participation->household_id !== (int) $household->id) {
            $participation->update(['household_id' => $household->id]);
            $counts['households_linked']++;
        }
    }

    private function measurement(Surveyed $participation, int $day): SurveyedMeasurement
    {
        $measurement = SurveyedMeasurement::withTrashed()->firstOrCreate([
            'surveyed_id' => $participation->id,
            'day_number' => $day,
        ]);
        if ($measurement->trashed()) {
            $measurement->restore();
        }

        return $measurement;
    }

    private function activeResponses(Surveyed $participation): Collection
    {
        return SurveyedResponse::query()
            ->where('surveyed_id', $participation->id)
            ->with(['measurement', 'surveyed_responses_options'])
            ->orderBy('id')
            ->get();
    }

    private function archiveResponse(SurveyedResponse $response): void
    {
        $now = now();
        DB::table('surveyed_response_options')
            ->where('surveyed_response_id', $response->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now, 'updated_at' => $now]);
        $response->delete();
    }

    private function hasValue(SurveyedResponse $response): bool
    {
        return trim((string) $response->response_text) !== ''
            || filled($response->file_path)
            || $response->surveyed_responses_options->isNotEmpty();
    }

    private function snapshot(Proyect $project, Collection $surveys): array
    {
        $surveyIds = $surveys->pluck('id')->all();
        $participations = DB::table('surveyeds')
            ->whereIn('survey_id', $surveyIds)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('status', Surveyed::STATUS_FINALIZED)
                    ->orWhereNotNull('completed_at');
            })
            ->get();
        $participationIds = $participations->pluck('id')->all();
        $questionIds = DB::table('survey_questions')
            ->whereIn('survey_id', $surveyIds)
            ->pluck('id')
            ->all();
        $responses = DB::table('surveyed_responses')
            ->whereIn('surveyed_id', $participationIds)
            ->get();
        $offlineParticipations = DB::table('offline_sync_participations')
            ->whereIn('surveyed_id', $participationIds)
            ->get();

        return [
            'contract' => 'kpt-co2-instrument-migration-backup/1.0',
            'instrument_version' => config('kpt_co2.version'),
            'created_at' => now()->toIso8601String(),
            'project' => $project->toArray(),
            'surveys' => $surveys->map(fn (Survey $survey) => $survey->toArray())->all(),
            'questions' => DB::table('survey_questions')->whereIn('survey_id', $surveyIds)->get()->map(fn ($row) => (array) $row)->all(),
            'question_options' => DB::table('survey_question_options')->whereIn('survey_question_id', $questionIds)->get()->map(fn ($row) => (array) $row)->all(),
            'participations' => $participations->map(fn ($row) => (array) $row)->all(),
            'measurements' => DB::table('surveyed_measurements')->whereIn('surveyed_id', $participationIds)->get()->map(fn ($row) => (array) $row)->all(),
            'responses' => $responses->map(fn ($row) => (array) $row)->all(),
            'response_options' => DB::table('surveyed_response_options')->whereIn('surveyed_id', $participationIds)->get()->map(fn ($row) => (array) $row)->all(),
            'households' => DB::table('households')->whereIn('id', $participations->pluck('household_id')->filter())->get()->map(fn ($row) => (array) $row)->all(),
            'respondents' => DB::table('respondents')->whereIn('id', $participations->pluck('respondent_id')->filter())->get()->map(fn ($row) => (array) $row)->all(),
            'reopenings' => DB::table('surveyed_reopenings')->whereIn('surveyed_id', $participationIds)->get()->map(fn ($row) => (array) $row)->all(),
            'offline_participations' => $offlineParticipations->map(fn ($row) => (array) $row)->all(),
            'offline_measurements' => DB::table('offline_sync_measurements')->whereIn('offline_sync_participation_id', $offlineParticipations->pluck('id'))->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }

    private function storeBackup(Proyect $project, array $snapshot): array
    {
        $contents = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $compressed = gzencode($contents, 9);
        if ($compressed === false) {
            throw new RuntimeException('No se pudo comprimir el respaldo de las participaciones finalizadas.');
        }

        $path = sprintf(
            'survey-instrument-migration-backups/project-%d-%s-%s.json.gz',
            $project->id,
            now()->format('YmdHis'),
            Str::uuid()
        );
        if (! Storage::disk(self::BACKUP_DISK)->put($path, $compressed)) {
            throw new RuntimeException('No se pudo guardar el respaldo de las participaciones finalizadas.');
        }

        return [
            'path' => $path,
            'sha256' => hash('sha256', $contents),
        ];
    }

    private function emptyCounts(): array
    {
        return [
            'finalized_participations' => 0,
            'responses_moved_to_participation' => 0,
            'responses_moved_to_measurement' => 0,
            'duplicate_responses_archived' => 0,
            'obsolete_responses_archived' => 0,
            'inapplicable_responses_archived' => 0,
            'variants_inferred' => 0,
            'ambiguous_variants' => 0,
            'unresolved_variants' => 0,
            'households_created' => 0,
            'households_linked' => 0,
            'households_unresolved' => 0,
        ];
    }
}
