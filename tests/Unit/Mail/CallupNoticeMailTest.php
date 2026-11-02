<?php

namespace Tests\Unit\Mail;

use App\Club\Callups\CallupNoticeMode;
use App\Mail\CallupNoticeMail;
use Tests\TestCase;

/** Mail del aviso de la convocatoria (TIZ-37). */
class CallupNoticeMailTest extends TestCase
{
    private function mail(CallupNoticeMode $mode, array $players): CallupNoticeMail
    {
        return new CallupNoticeMail(
            'Convocatoria Sub-15 · vs Club Norte · sáb. 7/11 · CAD',
            $mode,
            'Club Atlético Demo',
            'Sub-15',
            ['vs Club Norte', 'Sábado 7 de noviembre · Presentación 09:15 · Partido 10:00', 'Lugar: Estadio Cubierto'],
            $players,
        );
    }

    public function test_a_tutor_with_two_children_gets_both_personal_links(): void
    {
        $mail = $this->mail(CallupNoticeMode::Published, [
            ['name' => 'Ana', 'url' => 'http://localhost:3000/convocatoria/responder/demo.ana'],
            ['name' => 'Bea', 'url' => 'http://localhost:3000/convocatoria/responder/demo.bea'],
        ]);

        $mail->assertHasSubject('Convocatoria Sub-15 · vs Club Norte · sáb. 7/11 · CAD');
        $mail->assertSeeInHtml('Ana y Bea están convocados/as para el partido de Sub-15.', false);
        $mail->assertSeeInHtml('http://localhost:3000/convocatoria/responder/demo.ana', false);
        $mail->assertSeeInHtml('http://localhost:3000/convocatoria/responder/demo.bea', false);
        $mail->assertSeeInHtml('Confirmar si va Ana', false);
        $mail->assertSeeInHtml('Lugar: Estadio Cubierto', false);
        $mail->assertDontSeeInHtml('Hubo cambios', false);
    }

    public function test_the_update_and_reminder_say_why_they_are_sent(): void
    {
        $players = [['name' => 'Ana', 'url' => 'http://localhost:3000/convocatoria/responder/demo.ana']];

        $updated = $this->mail(CallupNoticeMode::Updated, $players);
        $updated->assertSeeInHtml('Hubo cambios en el partido.', false);
        $updated->assertSeeInHtml('Ana está convocado/a para el partido de Sub-15.', false);

        $this->mail(CallupNoticeMode::Reminder, $players)->assertSeeInHtml('Todavía no nos confirmaste si va.', false);
    }
}
