<?php

namespace App\Notifications;

use App\Models\Surveyed;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SurveyParticipationChanged extends Notification
{
    use Queueable;

    public function __construct(
        private Surveyed $surveyed,
        private User $actor,
        private string $action
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            'CREATED' => 'creó una participación',
            'UPDATED' => 'modificó una participación',
            'FINALIZED' => 'finalizó una participación',
        ];

        return [
            'event' => 'SURVEY_PARTICIPATION_'.$this->action,
            'action' => $this->action,
            'message' => sprintf(
                '%s %s de la encuesta %s.',
                $this->actor->names ?: $this->actor->username,
                $labels[$this->action] ?? 'actualizó una participación',
                $this->surveyed->survey?->survey_name ?? '#'.$this->surveyed->survey_id
            ),
            'surveyed_id' => $this->surveyed->id,
            'survey_id' => $this->surveyed->survey_id,
            'survey_name' => $this->surveyed->survey?->survey_name,
            'household_code' => $this->surveyed->household?->code,
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->names,
                'username' => $this->actor->username,
            ],
        ];
    }
}
