<?php

namespace Tests\Feature\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_lists_own_notifications_paginated(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        $this->inTenant($this->tenantA(), function () use ($user) {
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();

            for ($i = 1; $i <= 25; $i++) {
                Notification::query()->create([
                    'club_user_id' => $clubUser->id,
                    'type' => 'callup_pending',
                    'title' => "Notificación {$i}",
                    'body' => "Cuerpo de prueba {$i}",
                    'data' => ['index' => $i],
                    'read_at' => $i % 2 === 0 ? now() : null,
                ]);
            }
        });

        // Default pagination: 20 per page
        $response = $this->withClubToken($token)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'title', 'body', 'data', 'read_at', 'created_at'],
                ],
                'links',
                'meta',
            ]);

        // Unread filter
        $this->withClubToken($token)->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(13, 'data'); // 25 total, 12 even (read), 13 odd (unread)
    }

    public function test_user_cannot_access_or_modify_notifications_of_another_user(): void
    {
        $user1 = User::factory()->create();
        $token1 = $this->clubToken($this->tenantA(), $user1, ['coach']);

        $user2 = User::factory()->create();
        $token2 = $this->clubToken($this->tenantA(), $user2, ['coordinator']);

        $notificationOfUser2 = $this->inTenant($this->tenantA(), function () use ($user2) {
            $clubUser2 = ClubUser::query()->where('global_user_id', $user2->id)->sole();

            return Notification::query()->create([
                'club_user_id' => $clubUser2->id,
                'type' => 'requirement_expiring',
                'title' => 'Alerta para User 2',
                'body' => 'Contenido privado',
                'data' => ['secret' => 123],
            ]);
        });

        // User 1 cannot see User 2's notifications in list
        $this->withClubToken($token1)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // User 1 cannot mark as read User 2's notification (responds 404, not 403, for no enumeration)
        $this->withClubToken($token1)->patchJson("/api/notifications/{$notificationOfUser2->id}/read")
            ->assertNotFound();

        $this->assertNull($this->inTenant($this->tenantA(), fn () => Notification::find($notificationOfUser2->id)->read_at));
    }

    public function test_marks_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        $notification = $this->inTenant($this->tenantA(), function () use ($user) {
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();

            return Notification::query()->create([
                'club_user_id' => $clubUser->id,
                'type' => 'callup_pending',
                'title' => 'Partido pendiente',
                'body' => 'La convocatoria no está publicada',
            ]);
        });

        $this->assertNull($notification->read_at);

        $response = $this->withClubToken($token)
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id);

        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => Notification::find($notification->id)->read_at));
    }

    public function test_marks_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        $this->inTenant($this->tenantA(), function () use ($user) {
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();

            Notification::query()->create([
                'club_user_id' => $clubUser->id,
                'type' => 'callup_pending',
                'title' => 'Alerta 1',
                'body' => 'Cuerpo 1',
            ]);

            Notification::query()->create([
                'club_user_id' => $clubUser->id,
                'type' => 'registration_closing',
                'title' => 'Alerta 2',
                'body' => 'Cuerpo 2',
            ]);
        });

        $this->withClubToken($token)
            ->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('affected', 2);

        $unreadCount = $this->inTenant($this->tenantA(), fn () => Notification::query()->whereNull('read_at')->count());
        $this->assertSame(0, $unreadCount);
    }
}
