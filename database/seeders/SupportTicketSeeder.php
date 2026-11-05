<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tickets de soporte de ejemplo para el panel de plataforma.
 *
 * Usa datos fijos y no factories: la demo también se carga en Laravel Cloud, donde no se instalan
 * las dependencias de desarrollo (Faker). Si ya hay tickets, no hace nada.
 */
class SupportTicketSeeder extends Seeder
{
    private const TICKETS = [
        // [nombre, email, club, asunto, mensaje, estado, nota del admin]
        ['Carla Méndez', 'carla.mendez@example.com', 'Club Atlético Las Heras', 'Quiero probar Sportiva en mi club', 'Somos un club de barrio con fútbol y vóley. ¿Cómo hacemos para empezar a usar la plataforma?', TicketStatus::New, null],
        ['Jorge Ibáñez', 'jorge.ibanez@example.com', 'Sociedad de Fomento Villa Sarmiento', 'Consulta por el plan Club Pro', '¿El plan Club Pro incluye más de un administrador? Tenemos tres personas en la comisión directiva.', TicketStatus::New, null],
        ['Valeria Sosa', 'valeria.sosa@example.com', null, 'No me llega el mail de invitación', 'Me invitaron como profe pero el mail no aparece en la bandeja de entrada ni en spam.', TicketStatus::New, null],
        ['Lucas Ferreyra', 'lucas.ferreyra@example.com', 'Club Social y Deportivo Ramos', 'Importar jugadores desde Excel', 'Tenemos el padrón en una planilla. ¿Se puede cargar todo junto en vez de uno por uno?', TicketStatus::New, null],
        ['Mariana Quiroga', 'mariana.quiroga@example.com', 'Club Defensores de Haedo', 'Pedido de demo para la comisión', 'Nos gustaría ver una demostración el próximo martes a la noche.', TicketStatus::New, null],
        ['Hernán Castillo', 'hernan.castillo@example.com', 'Club Náutico del Oeste', 'Error al subir un apto médico', 'Al subir un PDF de 3 MB aparece un error y el documento no queda cargado.', TicketStatus::InReview, 'Revisando el límite de tamaño de los documentos.'],
        ['Paula Giménez', 'paula.gimenez@example.com', 'Club Atlético Morón Sur', 'Cambiar el nombre corto del club', 'Necesitamos que el nombre corto sea CAMS en lugar de CAM.', TicketStatus::InReview, null],
        ['Ricardo Vera', 'ricardo.vera@example.com', null, 'Duda sobre roles', '¿Un utilero puede ver los aptos médicos de los jugadores?', TicketStatus::InReview, null],
        ['Silvina Arce', 'silvina.arce@example.com', 'Club Juventud Unida', 'No puedo entrar a mi cuenta', 'Olvidé la contraseña y el link de recuperación me dice que venció.', TicketStatus::Resolved, 'Se reenvió el link de recuperación; el anterior había vencido.'],
        ['Gustavo Moreno', 'gustavo.moreno@example.com', 'Club El Porvenir', 'Agregar una disciplina nueva', 'Queremos sumar hockey a las categorías del club.', TicketStatus::Resolved, 'Se explicó cómo dar de alta el deporte desde Configuración.'],
        ['Andrea Luna', 'andrea.luna@example.com', 'Club Deportivo San Martín', 'Convocatoria sin respuesta de las familias', 'Las familias dicen que el link de la convocatoria no abre.', TicketStatus::Resolved, 'El club tenía mal cargado el mail de contacto de varios tutores.'],
        ['Federico Ríos', 'federico.rios@example.com', null, 'Factura del mes', 'Necesito la factura de octubre a nombre del club.', TicketStatus::Resolved, 'Factura enviada por mail.'],
        ['Spam Bot', 'ofertas@example.com', null, 'Oferta imperdible', 'Compre seguidores para las redes de su club.', TicketStatus::Discarded, 'Descartado por duplicado o spam.'],
        ['Carla Méndez', 'carla.mendez@example.com', 'Club Atlético Las Heras', 'Quiero probar Sportiva en mi club', 'Mensaje enviado dos veces.', TicketStatus::Discarded, 'Descartado por duplicado o spam.'],
    ];

    public function run(): void
    {
        if (SupportTicket::query()->exists()) {
            return;
        }

        $admin = User::query()->where('is_platform_admin', true)->first();

        foreach (self::TICKETS as [$name, $email, $club, $subject, $message, $status, $notes]) {
            $handled = in_array($status, [TicketStatus::Resolved, TicketStatus::Discarded], true);

            SupportTicket::create([
                'name' => $name,
                'email' => $email,
                'club_name' => $club,
                'subject' => $subject,
                'message' => $message,
                'status' => $status,
                'admin_notes' => $notes,
                'handled_by' => $handled ? $admin?->id : null,
                'resolved_at' => $status === TicketStatus::Resolved ? now() : null,
            ]);
        }
    }
}
