<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subscribe_stores_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
            'keys' => [
                'p256dh' => 'BIIU61NiTMIfZ0jpZKRT9zV6ZQzVFtKijD1h63Y4SldFbExKWpaHva9fBQLSuw9GSQkITgC_T48nAC9VT-J8W9U',
                'auth' => '8lh-4dewroNxwugq_mqJFifQ_Eo6FB_NB2hjNVxRKXc',
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        ]);
    }

    public function test_subscribe_is_idempotent(): void
    {
        $user = User::factory()->create();
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-456',
            'keys' => [
                'p256dh' => 'BIIU61NiTMIfZ0jpZKRT9zV6ZQzVFtKijD1h63Y4SldFbExKWpaHva9fBQLSuw9GSQkITgC_T48nAC9VT-J8W9U',
                'auth' => '8lh-4dewroNxwugq_mqJFifQ_Eo6FB_NB2hjNVxRKXc',
            ],
        ];

        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();
        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();

        $this->assertEquals(1, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_unsubscribe_removes_subscription(): void
    {
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-789',
            'p256dh' => 'key',
            'auth' => 'auth',
        ]);

        $this->actingAs($user)->postJson('/push/unsubscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-789',
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-789',
        ]);
    }

    public function test_subscribe_requires_auth(): void
    {
        $this->postJson('/push/subscribe', [
            'endpoint' => 'https://example.com/endpoint',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnauthorized();
    }
}
