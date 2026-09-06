<?php

namespace App\Console\Commands;

use App\Models\Proyect;
use App\Services\GeobosquesSurveyConfigurator;
use DomainException;
use Illuminate\Console\Command;

class ConfigureGeobosquesSurvey extends Command
{
    protected $signature = 'survey:configure-geobosques
                            {project : ID del proyecto al que pertenecerá la encuesta}';

    protected $description = 'Configura como inactiva la encuesta dinámica de presión sobre el bosque';

    public function handle(GeobosquesSurveyConfigurator $configurator): int
    {
        $project = Proyect::find($this->argument('project'));

        if (! $project) {
            $this->error('El proyecto indicado no existe o fue eliminado.');

            return self::FAILURE;
        }

        try {
            $survey = $configurator->configure($project);
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Encuesta GeoBosques configurada con ID {$survey->id}.");
        $this->line('Estado: INACTIVA (pendiente de validación funcional antes de publicar).');
        $this->line('Preguntas dinámicas: '.$survey->survey_questions->count().'.');
        $this->warn('El Excel no define obligatoriedad; se configuraron todos los campos como obligatorios para revisión.');

        return self::SUCCESS;
    }
}
