<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Una POST solo puede tener una PRE (Survey::preSurvey es hasOne). En producción la encuesta
 * archivada 9 y "KPT línea base" (10) apuntaban ambas a "KPT monitoreo" (11), y el monitoreo
 * validaba los hogares contra la 9, rechazando líneas base finalizadas de la 10.
 *
 * Por cada POST con varias PRE activas se conserva la que tiene más participaciones
 * finalizadas (desempate: la más reciente) y se desvinculan las demás. No toca participaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicatedPostIds = DB::table('surveys')
            ->whereNull('deleted_at')
            ->whereNotNull('post_survey_id')
            ->groupBy('post_survey_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('post_survey_id');

        foreach ($duplicatedPostIds as $postId) {
            $pres = DB::table('surveys')
                ->whereNull('deleted_at')
                ->where('post_survey_id', $postId)
                ->get(['id'])
                ->map(fn ($pre) => [
                    'id' => (int) $pre->id,
                    'finalized' => DB::table('surveyeds')
                        ->where('survey_id', $pre->id)
                        ->whereNull('deleted_at')
                        ->where('status', 'FINALIZADA')
                        ->count(),
                ])
                ->sort(fn (array $a, array $b) => [$b['finalized'], $b['id']] <=> [$a['finalized'], $a['id']])
                ->values();

            $keeperId = $pres->first()['id'];
            $unlinkedIds = $pres->pluck('id')->reject(fn (int $id) => $id === $keeperId)->values()->all();

            DB::table('surveys')->whereIn('id', $unlinkedIds)->update([
                'post_survey_id' => null,
                'updated_at' => now(),
            ]);

            Log::info('PRE duplicadas desvinculadas de la POST', [
                'post_survey_id' => (int) $postId,
                'kept_pre_survey_id' => $keeperId,
                'unlinked_pre_survey_ids' => $unlinkedIds,
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible a propósito: restaurar el vínculo duplicado reintroduciría el error.
    }
};
