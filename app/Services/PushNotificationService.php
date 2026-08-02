<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    protected function webPush(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.vapid.subject'),
                'publicKey' => config('services.webpush.vapid.public_key'),
                'privateKey' => config('services.webpush.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Send a push notification to all devices a user has registered.
     *
     * @return int number of successful deliveries
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null, array $extra = []): int
    {
        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = json_encode(array_merge([
            'title' => $title,
            'body' => $body,
            'icon' => '/pwa/icons/icon-192.png',
            'badge' => '/pwa/icons/icon-192.png',
            'url' => $url ?? '/',
        ], $extra));

        $webPush = $this->webPush();

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh,
                    'authToken' => $sub->auth,
                ]),
                $payload,
                ['TTL' => 86400], // 24 hours — enough for a missed delivery to be picked up
            );
        }

        $sent = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                continue;
            }

            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                Log::info("Removed expired push subscription: " . $report->getEndpoint());
            } else {
                Log::warning("Push delivery failed: " . $report->getReason());
            }
        }

        return $sent;
    }

    /**
     * Send to many users. Each user gets one notification per call.
     *
     * @param iterable<User> $users
     */
    public function sendToUsers(iterable $users, string $title, string $body, ?string $url = null, array $extra = []): int
    {
        $total = 0;
        foreach ($users as $user) {
            $total += $this->sendToUser($user, $title, $body, $url, $extra);
        }
        return $total;
    }
}
