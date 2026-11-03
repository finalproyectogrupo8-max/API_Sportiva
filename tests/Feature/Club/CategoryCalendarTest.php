<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\CategoryCoach;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\GameMatch;
use App\Models\Club\Player;
use App\Models\Club\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Calendario suscribible por categoría (FRO-32). */
class CategoryCalendarTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_link_public_ics_and_regenerate(): void
    {
        [$u15, $u17] = $this->inTenant($this->tenantA(), function () {
            $u15 = Category::factory()->create(['name' => 'Sub-15']);
            $u17 = Category::factory()->create(['name' => 'Sub-17']);
            GameMatch::factory()->create(['category_id' => $u15->id, 'opponent' => 'Club Rival', 'match_date' => now()->addDays(3)->setTime(17, 0), 'venue' => 'Cancha 1']);
            Training::factory()->create(['category_id' => $u15->id, 'starts_at' => now()->addDays(1)->setTime(18, 0), 'ends_at' => now()->addDays(1)->setTime(19, 30)]);
            GameMatch::factory()->create(['category_id' => $u17->id, 'opponent' => 'Otra Categoría']);
            Player::factory()->create(['first_name' => 'Nombre', 'last_name' => 'Secreto']);

            return [$u15, $u17];
        });
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);

        $link = $this->withClubToken($admin)->getJson("/api/categories/{$u15->id}/calendar-link")->assertOk()->json('data');
        $this->assertStringStartsWith('webcal://', $link['webcal_url']);
        $this->assertStringContainsString('calendar.google.com', $link['google_url']);
        // Pedirlo de nuevo devuelve el mismo link.
        $this->assertSame($link['url'], $this->withClubToken($admin)->getJson("/api/categories/{$u15->id}/calendar-link")->json('data.url'));

        $path = parse_url($link['url'], PHP_URL_PATH);
        $ics = $this->get($path)->assertOk()->assertHeader('content-type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('Sub-15 vs Club Rival', $ics);
        $this->assertStringContainsString('Entrenamiento Sub-15', $ics);
        $this->assertStringContainsString('LOCATION:Cancha 1', $ics);
        $this->assertStringNotContainsString('Otra Categoría', $ics);
        $this->assertStringNotContainsString('Secreto', $ics);

        // Regenerar invalida el link anterior.
        $new = $this->withClubToken($admin)->postJson("/api/categories/{$u15->id}/calendar-link")->assertCreated()->json('data.url');
        $this->assertNotSame($link['url'], $new);
        $this->get($path)->assertNotFound();
        $this->get(parse_url($new, PHP_URL_PATH))->assertOk();
        $this->get('/api/public/calendar/no-existe.abc.ics')->assertNotFound();

        // El profe solo ve el link de sus categorías y no lo regenera.
        $coachUser = User::factory()->create();
        $coachToken = $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->inTenant($this->tenantA(), function () use ($coachUser, $u15) {
            $coach = Coach::factory()->create(['club_user_id' => ClubUser::where('global_user_id', $coachUser->id)->value('id')]);
            CategoryCoach::create(['category_id' => $u15->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);
        });
        $this->withClubToken($coachToken)->getJson("/api/categories/{$u15->id}/calendar-link")->assertOk();
        $this->withClubToken($coachToken)->getJson("/api/categories/{$u17->id}/calendar-link")->assertNotFound();
    }
}
