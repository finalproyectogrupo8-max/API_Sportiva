<?php

namespace Tests\Unit\Club;

use App\Club\Callups\CallupMessages;
use App\Club\Callups\CallupNoticeMode;
use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Club\Player;
use App\Models\Tenant;
use Tests\TestCase;

/** Textos del aviso por mail de la convocatoria (TIZ-37): dicen lo mismo que WhatsApp. */
class CallupMessagesTest extends TestCase
{
    private function match(): GameMatch
    {
        // En UTC: en Buenos Aires es el sábado 7/11 a las 10:00.
        $match = new GameMatch([
            'opponent' => 'Club Norte', 'match_date' => '2026-11-07 13:00:00', 'meet_at' => '2026-11-07 12:15:00',
            'venue' => 'Estadio Cubierto', 'is_home' => true, 'uniform' => 'home', 'uniform_color' => 'Azul',
        ]);

        return $match->setRelation('category', new Category(['name' => 'Sub-15']));
    }

    public function test_the_mail_summary_is_the_same_as_the_whatsapp_message(): void
    {
        $messages = new CallupMessages;
        $match = $this->match();
        $callup = (new MatchCallup)->forceFill(['token' => 'demo.abc'])
            ->setRelation('player', new Player(['first_name' => 'Ana', 'last_name' => 'Acosta']));

        $summary = $messages->summary($match);

        $this->assertStringContainsString("vs Club Norte\n", $summary);
        $this->assertStringContainsString('Presentación 09:15 · Partido 10:00', $summary);
        $this->assertStringContainsString('Lugar: Estadio Cubierto', $summary);
        $this->assertStringContainsString($summary, $messages->personalMessage($callup, $match));
    }

    public function test_the_subject_says_why_the_notice_is_sent(): void
    {
        $messages = new CallupMessages;
        $match = $this->match();
        $tenant = new Tenant(['name' => 'Club Atlético Demo', 'short_name' => 'CAD']);

        $published = $messages->mailSubject($match, $tenant, CallupNoticeMode::Published);

        $this->assertStringStartsWith('Convocatoria Sub-15 · vs Club Norte · ', $published);
        $this->assertStringContainsString('7/11', $published);
        $this->assertStringEndsWith(' · CAD', $published);
        $this->assertSame('Hubo cambios: '.$published, $messages->mailSubject($match, $tenant, CallupNoticeMode::Updated));
        $this->assertSame('Recordatorio: '.$published, $messages->mailSubject($match, $tenant, CallupNoticeMode::Reminder));
    }

    public function test_the_subject_uses_the_club_name_without_short_name(): void
    {
        $subject = (new CallupMessages)->mailSubject($this->match(), new Tenant(['name' => 'Club Atlético Demo']), CallupNoticeMode::Published);

        $this->assertStringEndsWith(' · Club Atlético Demo', $subject);
    }
}
