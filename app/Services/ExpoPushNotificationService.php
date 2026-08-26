<?php

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Models\MobilePushNotificationDelivery;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ExpoPushNotificationService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send(MobilePushNotification $notification, Collection $devices): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($devices->chunk(100) as $deviceChunk) {
            $deliveries = $deviceChunk->mapWithKeys(function (MobileDevice $device) use ($notification) {
                $delivery = MobilePushNotificationDelivery::create([
                    'mobile_push_notification_id' => $notification->id,
                    'mobile_device_id' => $device->id,
                    'token_snapshot' => $device->expo_push_token,
                    'status' => 'pending',
                ]);

                return [$device->id => $delivery];
            });

            $messages = $deviceChunk->map(fn (MobileDevice $device) => [
                'to' => $device->expo_push_token,
                'sound' => 'default',
                'title' => $notification->title,
                'body' => $notification->body,
                'data' => array_merge($notification->data ?? [], [
                    'notification_id' => $notification->id,
                ]),
                'priority' => 'high',
                'channelId' => 'default',
            ])->values()->all();

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(20)
                    ->retry(2, 500)
                    ->post(self::ENDPOINT, $messages);
                $response->throw();
                $tickets = $response->json('data');

                if (!is_array($tickets) || count($tickets) !== $deviceChunk->count()) {
                    throw new \RuntimeException('Réponse Expo incomplète pour le lot envoyé.');
                }

                foreach ($deviceChunk->values() as $index => $device) {
                    $ticket = $tickets[$index] ?? [];
                    $delivery = $deliveries->get($device->id);

                    if (($ticket['status'] ?? null) === 'ok') {
                        $delivery->update([
                            'status' => 'sent',
                            'ticket_id' => $ticket['id'] ?? null,
                            'sent_at' => now(),
                        ]);
                        $device->update([
                            'last_notification_at' => now(),
                            'last_error' => null,
                        ]);
                        $sent++;
                        continue;
                    }

                    $error = (string) ($ticket['message'] ?? 'Expo a refusé la notification.');
                    $delivery->update([
                        'status' => 'failed',
                        'error' => $error,
                    ]);
                    $device->update([
                        'last_error' => $error,
                        'notifications_enabled' => ($ticket['details']['error'] ?? null) !== 'DeviceNotRegistered',
                    ]);
                    $failed++;
                }
            } catch (RequestException|\RuntimeException $exception) {
                foreach ($deviceChunk as $device) {
                    $deliveries->get($device->id)?->update([
                        'status' => 'failed',
                        'error' => $exception->getMessage(),
                    ]);
                    $device->update(['last_error' => $exception->getMessage()]);
                    $failed++;
                }
            }
        }

        DB::transaction(function () use ($notification, $devices, $sent, $failed) {
            $notification->update([
                'recipient_count' => $devices->count(),
                'sent_count' => $sent,
                'failed_count' => $failed,
                'status' => $failed === 0 ? 'sent' : ($sent > 0 ? 'partial' : 'failed'),
                'sent_at' => now(),
            ]);
        });

        return compact('sent', 'failed');
    }
}
