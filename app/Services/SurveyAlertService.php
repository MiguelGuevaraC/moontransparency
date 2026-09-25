<?php

namespace App\Services;

use App\Models\Surveyed;
use App\Models\User;
use App\Notifications\SurveyParticipationChanged;
use Illuminate\Support\Facades\Notification;

class SurveyAlertService
{
    public function notifyAdministrators(Surveyed $surveyed, ?User $actor, string $action): void
    {
        if (! $actor?->isSurveyor()) {
            return;
        }

        $administrators = User::query()
            ->with('rol')
            ->where('status', User::STATUS_ACTIVE)
            ->get()
            ->filter(fn (User $user) => $user->isAdministrator());

        if ($administrators->isEmpty()) {
            return;
        }

        Notification::send(
            $administrators,
            new SurveyParticipationChanged(
                $surveyed->loadMissing(['survey', 'household']),
                $actor,
                $action
            )
        );
    }
}
