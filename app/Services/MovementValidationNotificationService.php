<?php

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Models\SiteMouvementPopulation;
use Illuminate\Database\QueryException;

class MovementValidationNotificationService
{
    public function __construct(
        private ExpoPushNotificationService $pushNotificationService
    ) {
    }

    public function notify(SiteMouvementPopulation $movement, int $createdBy): bool
    {
        $movement->loadMissing('site');

        $userIds = $movement->site
            ->assignedUsers()
            ->wherePivot('can_collect', true)
            ->pluck('users.id');

        $devices = MobileDevice::eligible()
            ->whereIn('user_id', $userIds)
            ->get();

        if ($devices->isEmpty()) {
            return false;
        }

        try {
            $notification = MobilePushNotification::create([
                'created_by' => $createdBy,
                'title' => $movement->statut === 'valide'
                    ? 'Mouvement validé'
                    : 'Mouvement non validé',
                'body' => $this->body($movement),
                'data' => [
                    'type' => 'movement_validation',
                    'movement_id' => $movement->id,
                    'site_id' => $movement->site_id,
                    'status' => $movement->statut,
                ],
            ]);

            $this->pushNotificationService->send($notification, $devices);
        } catch (QueryException $exception) {
            report($exception);

            return false;
        }

        return true;
    }

    private function body(SiteMouvementPopulation $movement): string
    {
        $date = $movement->date_mouvement?->format('d/m/Y') ?? 'date inconnue';
        $details = sprintf(
            '%s ménages, %s individus',
            number_format((int) $movement->menages, 0, ',', ' '),
            number_format((int) $movement->individus, 0, ',', ' ')
        );

        if ($movement->statut === 'valide') {
            return sprintf(
                'Le mouvement du %s (%s) pour le site %s a été validé : %s.',
                $date,
                $movement->type_mouvement,
                $movement->site->nom,
                $details
            );
        }

        return sprintf(
            'Le mouvement du %s (%s) pour le site %s n’a pas été validé. Motif : %s.',
            $date,
            $movement->type_mouvement,
            $movement->site->nom,
            $movement->rejection_reason
        );
    }
}
