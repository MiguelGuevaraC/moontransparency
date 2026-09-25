<?php

namespace App\Console\Commands;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\User;
use App\Services\KptCo2FinalizedMigrationService;
use App\Services\KptCo2SurveyConfigurator;
use App\Services\SurveyCleanupService;
use DomainException;
use Illuminate\Console\Command;
use Throwable;

class SyncKptCo2Surveys extends Command
{
    protected $signature = 'survey:sync-kpt-co2
                            {project : ID del proyecto}
                            {--apply : Ejecuta la sincronización; sin esta opción solo muestra el plan}
                            {--clean-drafts : Respalda y elimina los borradores incompatibles antes de sincronizar}
                            {--migrate-finalized : Respalda y transforma las participaciones finalizadas sin eliminarlas}
                            {--actor= : ID del administrador responsable de la limpieza}
                            {--confirmation= : Debe ser SYNC_KPT_CO2 para limpiar o migrar datos}';

    protected $description = 'Sincroniza KPT línea base y monitoreo con el instrumento CO2 versionado';

    public function handle(
        KptCo2SurveyConfigurator $configurator,
        SurveyCleanupService $cleanupService,
        KptCo2FinalizedMigrationService $finalizedMigrationService
    ): int {
        $project = Proyect::find($this->argument('project'));
        if (! $project) {
            $this->error('El proyecto indicado no existe o fue eliminado.');

            return self::FAILURE;
        }

        $plan = $configurator->inspect($project);
        $this->table(
            ['Encuesta', 'ID', 'Estado', 'Preguntas', 'Esperadas', 'Borradores', 'Finalizadas'],
            collect($plan)->map(fn (array $row) => [
                $row['name'],
                $row['survey_id'] ?? 'NUEVA',
                $row['status'] ?? Survey::STATUS_INACTIVE,
                $row['questions_current'],
                $row['questions_expected'],
                $row['drafts'],
                $row['finalized'],
            ])->all()
        );

        if (! $this->option('apply')) {
            $this->info('Vista previa completada. No se modificó la base de datos.');
            $this->line('Para ejecutar: use --apply; agregue --clean-drafts si hay borradores y --migrate-finalized si hay finalizadas. Las operaciones con datos requieren --actor y --confirmation=SYNC_KPT_CO2.');

            return self::SUCCESS;
        }

        $hasDrafts = collect($plan)->sum('drafts') > 0;
        $hasFinalized = collect($plan)->sum('finalized') > 0;
        if ($hasDrafts && ! $this->option('clean-drafts')) {
            $this->error('Existen borradores incompatibles. Repita con --clean-drafts para respaldarlos y limpiarlos.');

            return self::FAILURE;
        }
        if ($hasFinalized && ! $this->option('migrate-finalized')) {
            $this->error('Existen participaciones finalizadas. Repita con --migrate-finalized para respaldarlas y transformarlas sin eliminarlas.');

            return self::FAILURE;
        }

        $actor = null;
        if ($hasDrafts || $hasFinalized) {
            if ($this->option('confirmation') !== config('kpt_co2.confirmation')) {
                $this->error('La confirmación no coincide. Use --confirmation=SYNC_KPT_CO2.');

                return self::FAILURE;
            }

            $actor = User::with('rol')->find($this->option('actor'));
            if (! $actor || ! $actor->isAdministrator()) {
                $this->error('Debe indicar con --actor el ID de un administrador válido.');

                return self::FAILURE;
            }
        }

        try {
            $audits = [];
            if ($hasDrafts) {
                foreach ($configurator->findConfiguredSurveys($project) as $survey) {
                    if (! $survey->surveyeds()->where('status', Surveyed::STATUS_DRAFT)->exists()) {
                        continue;
                    }

                    $audits[] = $cleanupService->clean(
                        $survey->id,
                        $actor,
                        'Sincronización controlada del instrumento KPT CO2 '.config('kpt_co2.version'),
                        config('kpt_co2.confirmation'),
                        [Surveyed::STATUS_DRAFT]
                    );
                }
            }

            $migration = $hasFinalized
                ? $finalizedMigrationService->migrate($project, $actor, $configurator)
                : null;
            $surveys = $migration['surveys'] ?? $configurator->configure($project);
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('La sincronización falló: '.$exception->getMessage());

            return self::FAILURE;
        }

        foreach (array_filter($audits) as $audit) {
            $this->line("Respaldo encuesta {$audit->survey_id}: {$audit->backup_path}");
        }
        if ($migration) {
            $this->line('Respaldo finalizadas: '.$migration['audit']->backup_path);
            $this->table(
                ['Resultado de migración', 'Cantidad'],
                collect($migration['counts'])->map(fn ($count, $label) => [$label, $count])->values()->all()
            );
            foreach ($migration['warnings'] as $warning) {
                $this->warn($warning);
            }
        }

        $this->info('Encuestas KPT CO2 sincronizadas correctamente.');
        $this->line("Línea base: ID {$surveys['baseline']->id}, {$surveys['baseline']->survey_questions->count()} preguntas.");
        $this->line("Monitoreo: ID {$surveys['monitoring']->id}, {$surveys['monitoring']->survey_questions->count()} preguntas.");
        $this->line("Vinculación PRE → POST: {$surveys['baseline']->id} → {$surveys['monitoring']->id}.");

        return self::SUCCESS;
    }
}
