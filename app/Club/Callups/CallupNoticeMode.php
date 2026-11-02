<?php

namespace App\Club\Callups;

/** Por qué sale el aviso por mail de la convocatoria (TIZ-37): define a quién se le manda y el asunto. */
enum CallupNoticeMode: string
{
    /** Primera publicación, o republicación sin cambios: solo a los que todavía no recibieron el aviso. */
    case Published = 'published';

    /** Republicación después de un cambio en el partido: a todos, avisando que hubo cambios. */
    case Updated = 'updated';

    /** Recordatorio: solo a los que siguen sin responder. */
    case Reminder = 'reminder';
}
