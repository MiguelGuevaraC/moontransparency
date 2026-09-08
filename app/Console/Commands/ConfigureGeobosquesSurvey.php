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
        $this->line('Estado: INACTIVA (lista para publicarse desde el módulo dinámico).');
        $this->line('Preguntas dinámicas: '.$survey->survey_questions->count().'.');
        $this->line('Los nueve campos visibles están configurados como obligatorios.');

        return self::SUCCESS;
    }
}
