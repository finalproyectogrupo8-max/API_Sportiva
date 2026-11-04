<?php

namespace Database\Seeders\Demo;

use App\Models\Club\Order;
use App\Models\Tenant;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Llena todas las tablas de la base de un club con datos coherentes entre sí. Corre dentro de
 * TenantDatabase::run, así que todas las consultas van a la conexión "tenant".
 *
 * Las filas se insertan con el query builder y no con los modelos, así no dependen de $fillable
 * ni de observers: es un volcado de datos de ejemplo, no un flujo de negocio.
 */
class ClubDemoSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $sport = [];

    /** @var array<string, int> */
    private array $club = [];

    /** @var list<int> */
    private array $cat = [];

    /** @var list<int> */
    private array $player = [];

    /** @var list<int> */
    private array $coach = [];

    /** @var list<int> */
    private array $competition = [];

    /** @var list<int> */
    private array $venue = [];

    /** @var list<int> */
    private array $training = [];

    /** @var list<int> */
    private array $match = [];

    /** @var list<int> */
    private array $location = [];

    /** @var list<int> */
    private array $material = [];

    /** @var list<int> */
    private array $apparel = [];

    /** @var list<int> */
    private array $component = [];

    /** @var list<int> */
    private array $repair = [];

    /** @var array<int, int> */
    private array $requirementIds = [];

    private Carbon $now;

    private int $activeSeason;

    /** @param  array<string, int>  $clubUsers  email => club_user id */
    public function run(Tenant $tenant, array $clubUsers): void
    {
        $this->now = now();
        $this->club = $clubUsers;

        $this->sports();
        $this->seasons();
        $this->categories();
        $this->competitions();
        $this->competitionRequirements();
        $this->competitionStandings();
        $this->seasonObjectives();
        $this->venues();
        $this->facilities();
        $this->coaches();
        $this->players();
        $this->positions();
        $this->documentsAndRequirements();
        $this->trainingsAndMatches();
        $this->attendanceAndEvaluations();
        $this->seasonHistory();
        $this->inventory();
        $this->orders();
        $this->maintenance();
        $this->announcements();
        $this->notificationsAndAlerts();
        $this->auditLog();
    }

    private function t(string $table): Builder
    {
        return DB::connection('tenant')->table($table);
    }

    /** Timestamps de una fila que los tiene. */
    private function ts(): array
    {
        return ['created_at' => $this->now, 'updated_at' => $this->now];
    }

    private function sports(): void
    {
        foreach (['Fútbol', 'Básquet', 'Hockey'] as $name) {
            $this->sport[$name] = $this->t('sports')->insertGetId(['name' => $name, 'is_active' => true] + $this->ts());
        }

        // Cada coordinador de deporte ve solo el suyo: Pablo, fútbol; Diego, básquet.
        $this->t('club_user_sports')->insert([
            ['club_user_id' => $this->club['coordinador.futbol@sportiva.com'], 'sport_id' => $this->sport['Fútbol']] + $this->ts(),
            ['club_user_id' => $this->club['coordinador@sportiva.com'], 'sport_id' => $this->sport['Básquet']] + $this->ts(),
        ]);
    }

    private function seasons(): void
    {
        $this->t('seasons')->insert([
            ['name' => '2025', 'start_date' => '2025-03-01', 'end_date' => '2025-12-15', 'status' => 'closed'] + $this->ts(),
        ]);
        $this->activeSeason = $this->t('seasons')->insertGetId([
            'name' => '2026 - segunda mitad', 'start_date' => '2026-03-01', 'end_date' => '2026-12-15', 'status' => 'active',
        ] + $this->ts());
    }

    private function categories(): void
    {
        $defs = [
            ['Sub-13 Masculino', 'Fútbol', 'male', 12, 13],
            ['Sub-15 Femenino', 'Fútbol', 'female', 14, 15],
            ['Sub-17 Mixto', 'Básquet', 'mixed', 16, 17],
        ];

        foreach ($defs as [$name, $sport, $branch, $min, $max]) {
            $this->cat[] = $this->t('categories')->insertGetId([
                'sport_id' => $this->sport[$sport],
                'season_id' => $this->activeSeason,
                'name' => $name,
                'branch' => $branch,
                'min_age' => $min,
                'max_age' => $max,
                'is_active' => true,
                'calendar_token' => Str::random(40),
            ] + $this->ts());
        }

        // Una categoría de la temporada anterior: el historial de temporadas no queda vacío.
        $this->t('categories')->insert([
            'sport_id' => $this->sport['Fútbol'], 'season_id' => $this->activeSeason - 1,
            'name' => 'Sub-13 Masculino 2025', 'branch' => 'male', 'min_age' => 12, 'max_age' => 13, 'is_active' => false,
        ] + $this->ts());
    }

    private function competitions(): void
    {
        $this->competition[] = $this->t('competitions')->insertGetId([
            'sport_id' => $this->sport['Fútbol'], 'season_id' => $this->activeSeason,
            'name' => 'Liga Metropolitana Infantil', 'organizer' => 'Liga Metropolitana de Fútbol',
            'registration_deadline' => '2026-11-30',
        ] + $this->ts());

        $this->competition[] = $this->t('competitions')->insertGetId([
            'sport_id' => $this->sport['Básquet'], 'season_id' => $this->activeSeason,
            'name' => 'Torneo Juvenil de Básquet', 'organizer' => 'Federación Básquet Zona Norte',
            'registration_deadline' => '2026-12-05',
        ] + $this->ts());

        // Categorías que juega cada competencia.
        $this->t('category_competitions')->insert([
            ['category_id' => $this->cat[0], 'competition_id' => $this->competition[0]],
            ['category_id' => $this->cat[1], 'competition_id' => $this->competition[0]],
            ['category_id' => $this->cat[2], 'competition_id' => $this->competition[1]],
        ]);
    }

    private function competitionRequirements(): void
    {
        $futbol = [
            ['Apto médico', 12, true, false, true],
            ['Ficha de inscripción firmada', null, true, false, true],
            ['Copia del DNI', null, true, false, true],
            ['Autorización de los padres', null, true, true, true],
        ];
        $basquet = [
            ['Apto médico', 12, true, false, true],
            ['Copia del DNI', null, true, false, false],
        ];

        foreach ([[$this->competition[0], $futbol], [$this->competition[1], $basquet]] as [$competition, $defs]) {
            foreach ($defs as $i => [$name, $validity, $file, $minors, $required]) {
                $id = $this->t('competition_requirements')->insertGetId([
                    'competition_id' => $competition, 'name' => $name, 'validity_months' => $validity,
                    'requires_file' => $file, 'minors_only' => $minors, 'is_required' => $required, 'sort' => $i + 1,
                ] + $this->ts());
                $this->requirementIds[$competition][] = $id;
            }
        }
    }

    private function competitionStandings(): void
    {
        $teams = [
            ['Sub-13 Masculino', 'Club Demo Sportiva', true, 5, 4, 0, 1, 14, 5, 12],
            ['Sub-13 Masculino', 'Racing de Tigre', false, 5, 3, 1, 1, 11, 6, 10],
            ['Sub-13 Masculino', 'Deportivo San Fernando', false, 5, 2, 2, 1, 9, 7, 8],
            ['Sub-13 Masculino', 'Unión de Benavídez', false, 5, 1, 2, 2, 6, 9, 5],
            ['Sub-13 Masculino', 'Club Atlético Escobar', false, 5, 0, 3, 2, 4, 12, 3],
        ];

        foreach ($teams as $i => [$cat, $team, $own, $played, $won, $drawn, $lost, $for, $against, $points]) {
            $this->t('competition_standings')->insert([
                'competition_id' => $this->competition[0],
                'category_id' => $this->cat[0],
                'position' => $i + 1,
                'team_name' => $team,
                'is_own' => $own,
                'played' => $played, 'won' => $won, 'drawn' => $drawn, 'lost' => $lost,
                'goals_for' => $for, 'goals_against' => $against, 'points' => $points,
            ] + $this->ts());
        }
    }

    private function seasonObjectives(): void
    {
        $this->t('season_objectives')->insert([
            ['season_id' => $this->activeSeason, 'category_id' => null, 'title' => 'Llegar a la final de la liga', 'description' => 'Clasificar entre los cuatro primeros.', 'target_value' => 4, 'current_value' => 2, 'unit' => 'posición', 'status' => 'in_progress', 'due_date' => '2026-12-01', 'deleted_at' => null] + $this->ts(),
            ['season_id' => $this->activeSeason, 'category_id' => $this->cat[1], 'title' => 'Asistencia promedio del 85%', 'description' => null, 'target_value' => 85, 'current_value' => 88, 'unit' => '%', 'status' => 'achieved', 'due_date' => '2026-11-15', 'deleted_at' => null] + $this->ts(),
            ['season_id' => $this->activeSeason, 'category_id' => $this->cat[2], 'title' => 'Ganar 10 partidos', 'description' => null, 'target_value' => 10, 'current_value' => 4, 'unit' => 'partidos', 'status' => 'pending', 'due_date' => '2026-12-10', 'deleted_at' => null] + $this->ts(),
        ]);
    }

    private function venues(): void
    {
        $this->venue[] = $this->t('venues')->insertGetId([
            'name' => 'Cancha Principal', 'address' => 'Av. Libertador 1234, Tigre, Buenos Aires',
            'latitude' => -34.4226000, 'longitude' => -58.5796000, 'is_active' => true,
            'notes' => 'Cancha de césped sintético con iluminación.', 'deleted_at' => null,
        ] + $this->ts());
        $this->venue[] = $this->t('venues')->insertGetId([
            'name' => 'Parque Deportivo Norte', 'address' => 'Calle Los Ceibos 450, Benavídez, Buenos Aires',
            'latitude' => -34.4118000, 'longitude' => -58.6358000, 'is_active' => true,
            'notes' => null, 'deleted_at' => null,
        ] + $this->ts());
    }

    private function facilities(): void
    {
        $cancha = $this->t('facilities')->insertGetId([
            'name' => 'Cancha 1', 'type' => 'court', 'location' => 'Sede principal, sector norte', 'status' => 'active',
            'notes' => 'Arcos reforzados en mayo de 2026.', 'deleted_at' => null,
        ] + $this->ts());
        $vestuario = $this->t('facilities')->insertGetId([
            'name' => 'Vestuarios A', 'type' => 'locker_room', 'location' => 'Sede principal, edificio del quincho', 'status' => 'active',
            'notes' => null, 'deleted_at' => null,
        ] + $this->ts());

        $arco = $this->t('facility_components')->insertGetId([
            'facility_id' => $cancha, 'name' => 'Arco norte', 'condition_status' => 'review',
            'last_inspected_at' => $this->now->copy()->subDays(10), 'notes' => 'Red con un desgarro.',
        ] + $this->ts());
        $this->component[] = $arco;
        $this->component[] = $this->t('facility_components')->insertGetId([
            'facility_id' => $cancha, 'name' => 'Iluminación', 'condition_status' => 'ok',
            'last_inspected_at' => $this->now->copy()->subDays(30), 'notes' => null,
        ] + $this->ts());
        $this->component[] = $this->t('facility_components')->insertGetId([
            'facility_id' => $vestuario, 'name' => 'Duchas', 'condition_status' => 'out_of_service',
            'last_inspected_at' => $this->now->copy()->subDays(2), 'notes' => 'Canilla de la ducha 3 sin agua caliente.',
        ] + $this->ts());
        $this->facilityIds = [$cancha, $vestuario];
    }

    /** @var list<int> */
    private array $facilityIds = [];

    private function coaches(): void
    {
        // Profe con usuario del sistema, preparador con usuario, y un ayudante sin usuario.
        $this->coach[] = $this->t('coaches')->insertGetId([
            'club_user_id' => $this->club['profe@sportiva.com'], 'first_name' => 'Martín', 'last_name' => 'Ledesma',
            'phone' => '+54 11 4000-1001', 'email' => 'profe@sportiva.com', 'status' => 'active', 'deleted_at' => null,
        ] + $this->ts());
        $this->coach[] = $this->t('coaches')->insertGetId([
            'club_user_id' => $this->club['preparador@sportiva.com'], 'first_name' => 'Sofía', 'last_name' => 'Bianchi',
            'phone' => '+54 11 4000-1002', 'email' => 'preparador@sportiva.com', 'status' => 'active', 'deleted_at' => null,
        ] + $this->ts());
        $this->coach[] = $this->t('coaches')->insertGetId([
            'club_user_id' => null, 'first_name' => 'Gonzalo', 'last_name' => 'Ferreyra',
            'phone' => '+54 11 4000-1003', 'email' => null, 'status' => 'active', 'deleted_at' => null,
        ] + $this->ts());

        $this->t('category_coaches')->insert([
            ['category_id' => $this->cat[0], 'coach_id' => $this->coach[0], 'assignment_type' => 'head_coach', 'start_date' => '2026-03-01', 'end_date' => null],
            ['category_id' => $this->cat[1], 'coach_id' => $this->coach[0], 'assignment_type' => 'head_coach', 'start_date' => '2026-03-01', 'end_date' => null],
            ['category_id' => $this->cat[1], 'coach_id' => $this->coach[2], 'assignment_type' => 'assistant', 'start_date' => '2026-03-01', 'end_date' => null],
            ['category_id' => $this->cat[2], 'coach_id' => $this->coach[2], 'assignment_type' => 'head_coach', 'start_date' => '2026-03-01', 'end_date' => null],
            ['category_id' => $this->cat[0], 'coach_id' => $this->coach[1], 'assignment_type' => 'physical_trainer', 'start_date' => '2026-03-01', 'end_date' => null],
            ['category_id' => $this->cat[1], 'coach_id' => $this->coach[1], 'assignment_type' => 'physical_trainer', 'start_date' => '2026-03-01', 'end_date' => null],
        ]);
    }

    private function players(): void
    {
        // [nombre, apellido, género, nacimiento, categoría índice, posición, lateralidad]
        $roster = [
            ['Tomás', 'Acosta', 'male', '2013-04-12', 0, 'Arquero', 'right'],
            ['Benjamín', 'Ríos', 'male', '2013-07-02', 0, 'Defensor', 'right'],
            ['Lautaro', 'Mendoza', 'male', '2013-01-19', 0, 'Mediocampista', 'left'],
            ['Felipe', 'Suárez', 'male', '2014-03-30', 0, 'Delantero', 'right'],
            ['Thiago', 'Villalba', 'male', '2013-09-08', 0, 'Mediocampista', 'both'],
            ['Santino', 'Correa', 'male', '2014-05-21', 0, 'Defensor', 'right'],
            ['Lucía', 'Gómez', 'female', '2011-06-14', 1, 'Delantera', 'right'],
            ['Valentina', 'Pereyra', 'female', '2011-02-03', 1, 'Mediocampista', 'right'],
            ['Julieta', 'Navarro', 'female', '2012-08-27', 1, 'Defensora', 'left'],
            ['Catalina', 'Ibarra', 'female', '2011-11-11', 1, 'Arquera', 'right'],
            ['Mía', 'Domínguez', 'female', '2012-04-05', 1, 'Mediocampista', 'right'],
            ['Emma', 'Quiroga', 'female', '2011-09-18', 1, 'Delantera', 'left'],
            ['Matías', 'Ortega', 'male', '2009-10-01', 2, 'Base', 'right'],
            ['Joaquín', 'Sosa', 'male', '2010-03-15', 2, 'Escolta', 'right'],
            ['Facundo', 'Medina', 'male', '2009-12-22', 2, 'Alero', 'left'],
            ['Agustín', 'Luna', 'male', '2010-07-07', 2, 'Pivot', 'right'],
            ['Bruno', 'Castillo', 'male', '2009-05-30', 2, 'Base', 'right'],
            ['Ignacio', 'Vera', 'male', '2010-01-25', 2, 'Escolta', 'both'],
        ];

        foreach ($roster as $i => [$first, $last, $gender, $birth, $catIndex, $position, $side]) {
            $playerId = $this->t('players')->insertGetId([
                'first_name' => $first, 'last_name' => $last, 'birth_date' => $birth, 'gender' => $gender,
                'document_number' => (string) (40000000 + $i * 137), 'registration_number' => 'FED-'.(2026000 + $i),
                'preferred_position' => $position, 'dominant_side' => $side, 'status' => 'active',
                'notes' => $i % 5 === 0 ? 'Llegó de la escuela de la zona en 2025.' : null, 'deleted_at' => null,
            ] + $this->ts());
            $this->player[] = $playerId;

            $this->t('player_category_memberships')->insert([
                'player_id' => $playerId, 'category_id' => $this->cat[$catIndex], 'start_date' => '2026-03-01',
                'end_date' => null, 'shirt_number' => ($i % 6) + 1, 'status' => 'active',
            ] + $this->ts());

            $this->t('player_contacts')->insert([
                'player_id' => $playerId, 'name' => 'Familiar de '.$first, 'relationship' => $i % 2 ? 'mother' : 'father',
                'phone' => '+54 11 5555-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                'email' => Str::lower($first.'.'.$last).'.familia@example.com', 'is_primary' => true,
            ] + $this->ts());
            if ($i % 4 === 0) {
                $this->t('player_contacts')->insert([
                    'player_id' => $playerId, 'name' => 'Abuela de '.$first, 'relationship' => 'guardian',
                    'phone' => null, 'email' => null, 'is_primary' => false,
                ] + $this->ts());
            }
        }
    }

    /** Posiciones de cada deporte y la de cada jugador (según su posición preferida). */
    private function positions(): void
    {
        $bySport = [
            'Fútbol' => ['Arquero', 'Defensor', 'Mediocampista', 'Delantero'],
            'Básquet' => ['Base', 'Escolta', 'Alero', 'Ala-pívot', 'Pivot'],
            'Hockey' => ['Arquera', 'Defensora', 'Volante', 'Delantera'],
        ];
        $ids = [];
        foreach ($bySport as $sport => $names) {
            foreach ($names as $name) {
                $ids[$sport][$name] = $this->t('positions')->insertGetId(['sport_id' => $this->sport[$sport], 'name' => $name, 'is_active' => true] + $this->ts());
            }
        }

        // Las dos primeras categorías son de fútbol y la tercera de básquet.
        $masculine = ['Arquera' => 'Arquero', 'Defensora' => 'Defensor', 'Delantera' => 'Delantero'];
        $players = $this->t('players')->whereIn('id', $this->player)->pluck('preferred_position', 'id');
        foreach ($this->player as $i => $playerId) {
            $sport = $i < 12 ? 'Fútbol' : 'Básquet';
            $name = (string) $players[$playerId];
            $positionId = $ids[$sport][$name] ?? $ids[$sport][$masculine[$name] ?? ''] ?? null;
            if ($positionId) {
                $this->t('player_positions')->insert(['player_id' => $playerId, 'position_id' => $positionId] + $this->ts());
            }
        }
    }

    private function documentsAndRequirements(): void
    {
        $profe = $this->club['profe@sportiva.com'];
        $documentIds = [];

        // Un papel presentado por jugador de las dos primeras categorías, con su archivo real en disco.
        foreach ($this->player as $i => $playerId) {
            if ($i >= 12) {
                continue;
            }
            $path = "demo/players/{$playerId}/apto-medico.txt";
            Storage::disk('local')->put($path, "Apto médico (archivo de demo) - jugador {$playerId}\n");
            $documentIds[$playerId] = $this->t('documents')->insertGetId([
                'documentable_type' => 'player', 'documentable_id' => $playerId, 'kind' => 'medical',
                'original_name' => 'apto-medico.txt', 'disk' => 'local', 'path' => $path, 'mime' => 'text/plain',
                'size' => strlen("Apto médico (archivo de demo) - jugador {$playerId}\n"), 'uploaded_by' => $profe, 'deleted_at' => null,
            ] + $this->ts());
        }

        foreach ($this->player as $i => $playerId) {
            $competition = $i < 12 ? 0 : 1;
            $requirements = $this->requirementIds[$this->competition[$competition]];

            foreach ($requirements as $j => $requirementId) {
                // Mayoría aprobada; algunos pendientes, rechazados o vencidos para mostrar los estados.
                $status = match (true) {
                    $i === 3 && $j === 0 => 'pending',
                    $i === 7 && $j === 1 => 'rejected',
                    $i === 10 && $j === 0 => 'expired',
                    $i === 14 && $j === 2 => 'submitted',
                    default => 'approved',
                };
                $documentId = $j === 0 ? ($documentIds[$playerId] ?? null) : null;

                $this->t('player_requirements')->insert([
                    'player_id' => $playerId,
                    'competition_requirement_id' => $requirementId,
                    'status' => $status,
                    'document_id' => $status === 'pending' ? null : $documentId,
                    'submitted_at' => $status === 'pending' ? null : $this->now->copy()->subDays(20),
                    'expires_at' => $j === 0 ? $this->now->copy()->addMonths($status === 'expired' ? -1 : 8)->toDateString() : null,
                    'reviewed_by' => $status === 'approved' || $status === 'rejected' ? $this->club['coordinador@sportiva.com'] : null,
                    'reviewed_at' => $status === 'approved' || $status === 'rejected' ? $this->now->copy()->subDays(15) : null,
                    'rejection_reason' => $status === 'rejected' ? 'La firma no coincide con el DNI. Volver a presentarla.' : null,
                ] + $this->ts());
            }
        }
    }

    private function trainingsAndMatches(): void
    {
        $profe = $this->coach[0];
        $prepa = $this->coach[1];

        $this->training[] = $this->t('trainings')->insertGetId([
            'category_id' => $this->cat[0], 'coach_id' => $profe,
            'starts_at' => $this->now->copy()->subDays(3)->setTime(18, 0), 'ends_at' => $this->now->copy()->subDays(3)->setTime(19, 30),
            'location' => 'Cancha Principal', 'objectives' => 'Circulación y pressing alto.', 'notes' => null, 'deleted_at' => null,
        ] + $this->ts());
        $this->training[] = $this->t('trainings')->insertGetId([
            'category_id' => $this->cat[1], 'coach_id' => $profe,
            'starts_at' => $this->now->copy()->addDays(2)->setTime(17, 30), 'ends_at' => $this->now->copy()->addDays(2)->setTime(19, 0),
            'location' => 'Parque Deportivo Norte', 'objectives' => 'Definición en el área.', 'notes' => 'Traer botines de repuesto.', 'deleted_at' => null,
        ] + $this->ts());
        $this->training[] = $this->t('trainings')->insertGetId([
            'category_id' => $this->cat[2], 'coach_id' => $this->coach[2],
            'starts_at' => $this->now->copy()->addDays(1)->setTime(19, 0), 'ends_at' => $this->now->copy()->addDays(1)->setTime(20, 30),
            'location' => 'Gimnasio Central', 'objectives' => null, 'notes' => null, 'deleted_at' => null,
        ] + $this->ts());
        $this->training[] = $this->t('trainings')->insertGetId([
            'category_id' => $this->cat[0], 'coach_id' => $prepa,
            'starts_at' => $this->now->copy()->subDays(10)->setTime(18, 0), 'ends_at' => $this->now->copy()->subDays(10)->setTime(19, 0),
            'location' => 'Cancha Principal', 'objectives' => 'Test de velocidad.', 'notes' => null, 'deleted_at' => null,
        ] + $this->ts());

        $venueMain = $this->venue[0];
        $fut = $this->competition[0];
        $bas = $this->competition[1];

        $this->match[] = $this->t('matches')->insertGetId([ // 0: próximo, convocatoria publicada
            'category_id' => $this->cat[0], 'competition_id' => $fut, 'opponent' => 'Racing de Tigre',
            'match_date' => $this->now->copy()->addDays(5)->setTime(10, 0), 'meet_at' => $this->now->copy()->addDays(5)->setTime(9, 15),
            'venue_id' => $venueMain, 'venue' => 'Cancha Principal', 'maps_url' => 'https://maps.google.com/?q=-34.4226,-58.5796',
            'is_home' => true, 'uniform' => 'home', 'uniform_color' => 'Azul', 'what_to_bring' => 'Botines, canilleras y un buzo.',
            'our_score' => null, 'opponent_score' => null, 'status' => 'scheduled', 'notes' => 'Confirmar arbitraje.',
            'callup_published_at' => $this->now->copy()->subDay(), 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
        ] + $this->ts());
        $this->match[] = $this->t('matches')->insertGetId([ // 1: jugado
            'category_id' => $this->cat[0], 'competition_id' => $fut, 'opponent' => 'Deportivo San Fernando',
            'match_date' => $this->now->copy()->subDays(7)->setTime(10, 0), 'meet_at' => null,
            'venue_id' => null, 'venue' => 'Estadio Municipal de San Fernando', 'maps_url' => null,
            'is_home' => false, 'uniform' => 'away', 'uniform_color' => 'Blanco', 'what_to_bring' => null,
            'our_score' => 3, 'opponent_score' => 1, 'status' => 'played', 'notes' => null,
            'callup_published_at' => $this->now->copy()->subDays(9), 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
        ] + $this->ts());
        $this->match[] = $this->t('matches')->insertGetId([ // 2: postergado
            'category_id' => $this->cat[1], 'competition_id' => $fut, 'opponent' => 'Unión de Benavídez',
            'match_date' => $this->now->copy()->addDays(9)->setTime(11, 0), 'meet_at' => null,
            'venue_id' => $this->venue[1], 'venue' => 'Parque Deportivo Norte', 'maps_url' => null,
            'is_home' => true, 'uniform' => 'home', 'uniform_color' => 'Celeste', 'what_to_bring' => null,
            'our_score' => null, 'opponent_score' => null, 'status' => 'postponed', 'notes' => 'Se reprograma por lluvia.',
            'callup_published_at' => null, 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
        ] + $this->ts());
        $this->match[] = $this->t('matches')->insertGetId([ // 3: jugado, básquet
            'category_id' => $this->cat[2], 'competition_id' => $bas, 'opponent' => 'Club Atlético Escobar',
            'match_date' => $this->now->copy()->subDays(14)->setTime(20, 0), 'meet_at' => null,
            'venue_id' => null, 'venue' => 'Gimnasio Central', 'maps_url' => null,
            'is_home' => true, 'uniform' => 'home', 'uniform_color' => 'Verde', 'what_to_bring' => null,
            'our_score' => 62, 'opponent_score' => 58, 'status' => 'played', 'notes' => null,
            'callup_published_at' => null, 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
        ] + $this->ts());
        $this->match[] = $this->t('matches')->insertGetId([ // 4: suspendido, básquet
            'category_id' => $this->cat[2], 'competition_id' => $bas, 'opponent' => 'Club Social San Isidro',
            'match_date' => $this->now->copy()->addDays(12)->setTime(20, 0), 'meet_at' => null,
            'venue_id' => null, 'venue' => 'Club San Isidro', 'maps_url' => null,
            'is_home' => false, 'uniform' => null, 'uniform_color' => null, 'what_to_bring' => null,
            'our_score' => null, 'opponent_score' => null, 'status' => 'cancelled', 'notes' => 'Cancelado por la federación.',
            'callup_published_at' => null, 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
        ] + $this->ts());

        // Convocatoria del partido próximo: los seis de Sub-13, con distintas respuestas.
        $responses = ['going', 'going', 'not_going', 'going', 'pending', 'going'];
        foreach (array_slice($this->player, 0, 6) as $i => $playerId) {
            $response = $responses[$i];
            $this->t('match_callups')->insert([
                'match_id' => $this->match[0], 'player_id' => $playerId, 'shirt_number' => ($i % 6) + 1,
                'response' => $response,
                'response_note' => $response === 'not_going' ? 'Viaja con la familia ese fin de semana.' : null,
                'responded_at' => $response === 'pending' ? null : $this->now->copy()->subHours(5),
                'notified_at' => $this->now->copy()->subDay(), 'token' => Str::random(32),
            ] + $this->ts());
        }
    }

    private function attendanceAndEvaluations(): void
    {
        $recordedBy = $this->club['profe@sportiva.com'];
        $statuses = ['present', 'present', 'late', 'excused', 'present', 'absent'];

        // Asistencia al entrenamiento de Sub-13 (ya pasó) y al partido jugado.
        foreach (array_slice($this->player, 0, 6) as $i => $playerId) {
            $this->t('player_attendance')->insert([
                'player_id' => $playerId, 'training_id' => $this->training[0], 'match_id' => null,
                'status' => $statuses[$i], 'notes' => $statuses[$i] === 'excused' ? 'Certificado de la escuela.' : null,
                'recorded_by' => $recordedBy,
            ] + $this->ts());
            $this->t('player_attendance')->insert([
                'player_id' => $playerId, 'training_id' => null, 'match_id' => $this->match[1],
                'status' => $i === 5 ? 'absent' : 'present', 'notes' => null, 'recorded_by' => $recordedBy,
            ] + $this->ts());
        }

        // Evaluaciones físicas de tres jugadoras de Sub-15, tomadas por el preparador.
        $evaluator = $this->coach[1];
        foreach ([6, 7, 8] as $n => $idx) {
            $evaluationId = $this->t('physical_evaluations')->insertGetId([
                'player_id' => $this->player[$idx], 'category_id' => $this->cat[1], 'evaluator_coach_id' => $evaluator,
                'evaluated_at' => $this->now->copy()->subDays(20 - $n)->setTime(17, 0),
                'summary' => 'Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.',
                'recommendations' => 'Trabajo de fondo dos veces por semana.', 'deleted_at' => null,
            ] + $this->ts());

            $this->t('physical_measurements')->insert([
                ['evaluation_id' => $evaluationId, 'metric_key' => 'sprint_30m', 'metric_name' => 'Sprint 30 m', 'numeric_value' => round(4.6 + $n * 0.1, 2), 'unit' => 's', 'text_value' => null, 'notes' => null],
                ['evaluation_id' => $evaluationId, 'metric_key' => 'salto_horizontal', 'metric_name' => 'Salto horizontal', 'numeric_value' => 160 + $n * 4, 'unit' => 'cm', 'text_value' => null, 'notes' => null],
                ['evaluation_id' => $evaluationId, 'metric_key' => 'lesiones', 'metric_name' => 'Lesiones previas', 'numeric_value' => null, 'unit' => null, 'text_value' => 'Ninguna', 'notes' => null],
            ]);
        }
    }

    /**
     * Tres meses de temporada para que Análisis y el inicio muestren tendencias: dos entrenamientos
     * por semana y un partido cada dos semanas por categoría, con su asistencia, y evaluaciones
     * físicas repetidas para ver la evolución. Usa una semilla fija: siempre genera lo mismo.
     */
    private function seasonHistory(): void
    {
        mt_srand(2026);
        $recordedBy = $this->club['profe@sportiva.com'];
        $monday = $this->now->copy()->startOfWeek();

        // Qué tan seguido viene cada jugador (de 100). Mía (índice 10) dejó de venir las últimas semanas.
        $commitment = [92, 88, 95, 80, 90, 72, 94, 86, 90, 97, 85, 78, 90, 84, 88, 93, 70, 82];
        $categoryPlayers = [array_slice($this->player, 0, 6, true), array_slice($this->player, 6, 6, true), array_slice($this->player, 12, 6, true)];
        $coaches = [$this->coach[0], $this->coach[0], $this->coach[2]];
        $places = ['Cancha Principal', 'Parque Deportivo Norte', 'Gimnasio Central'];
        $objectives = ['Pase y control orientado.', 'Defensa en bloque.', 'Transiciones rápidas.', 'Pelota parada.', 'Resistencia con pelota.', 'Juego reducido 4 vs 4.'];

        $statusFor = function (int $playerIndex, int $weeksAgo) use ($commitment): string {
            $chance = $commitment[$playerIndex];
            if ($playerIndex === 10 && $weeksAgo <= 3) {
                $chance = 15;
            }
            $roll = mt_rand(1, 100);

            return match (true) {
                $roll <= $chance - 6 => 'present',
                $roll <= $chance => 'late',
                $roll <= $chance + (100 - $chance) / 2 => 'excused',
                default => 'absent',
            };
        };

        $record = function (array $players, ?int $trainingId, ?int $matchId, int $weeksAgo) use ($statusFor, $recordedBy): void {
            $rows = [];
            foreach ($players as $index => $playerId) {
                $status = $statusFor($index, $weeksAgo);
                $rows[] = [
                    'player_id' => $playerId, 'training_id' => $trainingId, 'match_id' => $matchId, 'status' => $status,
                    'notes' => $status === 'excused' ? 'Avisó por WhatsApp.' : null, 'recorded_by' => $recordedBy,
                ] + $this->ts();
            }
            $this->t('player_attendance')->insert($rows);
        };

        // Entrenamientos: martes y jueves (fútbol) o lunes y miércoles (básquet), de hace 12 semanas a la pasada.
        for ($weeksAgo = 12; $weeksAgo >= 1; $weeksAgo--) {
            foreach ($categoryPlayers as $c => $players) {
                foreach ($c === 2 ? [0, 2] : [1, 3] as $dayOffset) {
                    $start = $monday->copy()->subWeeks($weeksAgo)->addDays($dayOffset)->setTime($c === 2 ? 19 : 18, 0);
                    $trainingId = $this->t('trainings')->insertGetId([
                        'category_id' => $this->cat[$c], 'coach_id' => $coaches[$c],
                        'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(90),
                        'location' => $places[$c], 'objectives' => $objectives[($weeksAgo + $dayOffset + $c) % count($objectives)], 'notes' => null, 'deleted_at' => null,
                    ] + $this->ts());
                    $record($players, $trainingId, null, $weeksAgo);
                }
            }
        }

        // Partidos jugados: un sábado (fútbol) o viernes (básquet) cada dos semanas.
        $opponents = [
            ['Tigre Juniors', 'Club Náutico Hacoaj', 'Atlético Pacheco', 'San Fernando FC', 'Villa La Ñata', 'Don Torcuato'],
            ['Las Leonas de Nordelta', 'Club Benavídez', 'Escobar Fútbol', 'Unión Pacheco', 'Rincón FC', 'Garín Sport'],
            ['Náutico San Isidro', 'Atenas de Tigre', 'Club Pilar Básquet', 'Olimpo Escobar', 'Don Bosco', 'Racing de Garín'],
        ];
        // Resultados [a favor, en contra], en orden de la más vieja a la más reciente.
        $scores = [
            [[2, 1], [0, 0], [1, 2], [3, 0], [2, 2], [1, 0]],
            [[1, 3], [2, 2], [2, 0], [0, 1], [3, 1], [2, 1]],
            [[58, 61], [72, 64], [66, 66], [70, 55], [61, 68], [77, 70]],
        ];
        foreach ($categoryPlayers as $c => $players) {
            foreach ([12, 10, 8, 6, 4, 2] as $n => $weeksAgo) {
                [$our, $their] = $scores[$c][$n];
                $date = $monday->copy()->subWeeks($weeksAgo)->addDays($c === 2 ? 4 : 5)->setTime($c === 2 ? 20 : 10, 0);
                $home = $n % 2 === 0;
                $matchId = $this->t('matches')->insertGetId([
                    'category_id' => $this->cat[$c], 'competition_id' => $this->competition[$c === 2 ? 1 : 0], 'opponent' => $opponents[$c][$n],
                    'match_date' => $date, 'meet_at' => $date->copy()->subMinutes(45),
                    'venue_id' => $home ? $this->venue[0] : null, 'venue' => $home ? 'Cancha Principal' : 'Cancha de '.$opponents[$c][$n], 'maps_url' => null,
                    'is_home' => $home, 'uniform' => $home ? 'home' : 'away', 'uniform_color' => $home ? 'Azul' : 'Blanco', 'what_to_bring' => null,
                    'our_score' => $our, 'opponent_score' => $their, 'status' => 'played', 'notes' => null,
                    'callup_published_at' => $date->copy()->subDays(2), 'public_token' => Str::random(32), 'updated_after_publish' => false, 'deleted_at' => null,
                ] + $this->ts());
                $record($players, null, $matchId, $weeksAgo);
            }
        }

        // Evaluaciones físicas cada mes: sprint que baja y salto que sube de a poco.
        $evaluator = $this->coach[1];
        foreach ([0, 1] as $c) {
            foreach ($categoryPlayers[$c] as $index => $playerId) {
                foreach ([80, 50] as $round => $daysAgo) {
                    $evaluationId = $this->t('physical_evaluations')->insertGetId([
                        'player_id' => $playerId, 'category_id' => $this->cat[$c], 'evaluator_coach_id' => $evaluator,
                        'evaluated_at' => $this->now->copy()->subDays($daysAgo + $index)->setTime(17, 0),
                        'summary' => $round === 0 ? 'Evaluación de inicio de temporada.' : 'Control de mitad de temporada: viene mejorando.',
                        'recommendations' => $round === 0 ? 'Trabajo de fuerza y técnica de carrera.' : 'Sumar pasadas cortas al final del entrenamiento.', 'deleted_at' => null,
                    ] + $this->ts());
                    $base = 4.9 + ($index % 3) * 0.1;
                    $this->t('physical_measurements')->insert([
                        ['evaluation_id' => $evaluationId, 'metric_key' => 'sprint_30m', 'metric_name' => 'Sprint 30 m', 'numeric_value' => round($base - $round * 0.15, 2), 'unit' => 's', 'text_value' => null, 'notes' => null],
                        ['evaluation_id' => $evaluationId, 'metric_key' => 'salto_horizontal', 'metric_name' => 'Salto horizontal', 'numeric_value' => 150 + $index * 3 + $round * 6, 'unit' => 'cm', 'text_value' => null, 'notes' => null],
                    ]);
                }
            }
        }
    }

    private function inventory(): void
    {
        $this->location[] = $this->t('storage_locations')->insertGetId([
            'name' => 'Depósito principal', 'location_code' => 'DEP-01', 'description' => 'Estante A, sede principal.', 'is_active' => true, 'deleted_at' => null,
        ] + $this->ts());
        $this->location[] = $this->t('storage_locations')->insertGetId([
            'name' => 'Vestuario visitante', 'location_code' => 'VES-02', 'description' => null, 'is_active' => true, 'deleted_at' => null,
        ] + $this->ts());

        foreach ([
            ['Conos de entrenamiento', 'Set de 20 conos naranjas.', 'unidad', 10],
            ['Pelotas de fútbol N°5', 'Pelotas oficiales de la liga.', 'unidad', 6],
            ['Chalecos de peto', 'Chalecos para partidillos.', 'unidad', 12],
            ['Botiquín de primeros auxilios', null, 'kit', 2],
        ] as [$name, $description, $unit, $minimum]) {
            $this->material[] = $this->t('materials')->insertGetId([
                'name' => $name, 'description' => $description, 'unit' => $unit, 'minimum_stock' => $minimum, 'is_active' => true,
            ] + $this->ts());
        }

        // Stock: en el depósito principal, en el vestuario, y uno con poco stock (alerta).
        $quantities = [[24, 0], [4, 3], [16, 0], [1, 1]];
        foreach ($this->material as $i => $materialId) {
            foreach ([0, 1] as $l) {
                $this->t('inventory_balances')->insert([
                    'material_id' => $materialId, 'location_id' => $this->location[$l], 'quantity' => $quantities[$i][$l],
                    'updated_at' => $this->now,
                ]);
            }
        }

        // Movimientos: ingresos, egresos, un ajuste y una transferencia entre depósitos.
        $performedBy = $this->club['utilero@sportiva.com'];
        $transfer = (string) Str::uuid();
        $this->t('inventory_movements')->insert([
            ['material_id' => $this->material[0], 'location_id' => $this->location[0], 'movement_type' => 'in', 'quantity' => 24, 'transfer_group_id' => null, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Compra inicial de temporada', 'reference' => 'FACT-0001', 'created_at' => $this->now->copy()->subDays(30)],
            ['material_id' => $this->material[1], 'location_id' => $this->location[0], 'movement_type' => 'in', 'quantity' => 6, 'transfer_group_id' => null, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Reposición', 'reference' => null, 'created_at' => $this->now->copy()->subDays(20)],
            ['material_id' => $this->material[1], 'location_id' => $this->location[0], 'movement_type' => 'transfer_out', 'quantity' => 3, 'transfer_group_id' => $transfer, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Envío al vestuario', 'reference' => null, 'created_at' => $this->now->copy()->subDays(5)],
            ['material_id' => $this->material[1], 'location_id' => $this->location[1], 'movement_type' => 'transfer_in', 'quantity' => 3, 'transfer_group_id' => $transfer, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Envío desde depósito principal', 'reference' => null, 'created_at' => $this->now->copy()->subDays(5)],
            ['material_id' => $this->material[2], 'location_id' => $this->location[0], 'movement_type' => 'adjustment', 'quantity' => -4, 'transfer_group_id' => null, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Inventario físico: dos chalecos rotos', 'reference' => null, 'created_at' => $this->now->copy()->subDays(2)],
            ['material_id' => $this->material[3], 'location_id' => $this->location[1], 'movement_type' => 'out', 'quantity' => 1, 'transfer_group_id' => null, 'order_id' => null, 'performed_by' => $performedBy, 'reason' => 'Uso en partido', 'reference' => null, 'created_at' => $this->now->copy()->subDay()],
        ]);

        foreach ([
            ['Camiseta titular', 'Camiseta de juego, con escudo bordado.'],
            ['Short de juego', 'Short con bolsillo lateral.'],
            ['Buzo de entrenamiento', 'Buzo con capucha, cierre completo.'],
        ] as [$name, $description]) {
            $this->apparel[] = $this->t('apparel_items')->insertGetId(['name' => $name, 'description' => $description, 'is_active' => true, 'deleted_at' => null] + $this->ts());
        }
    }

    private function orders(): void
    {
        $coord = $this->club['coordinador@sportiva.com'];
        $utilero = $this->club['utilero@sportiva.com'];
        $profe = $this->club['profe@sportiva.com'];
        $year = (int) $this->now->format('Y');

        $apparelOrders = [
            // [estado final, prioridad, descripción, historial]
            ['delivered', 'normal', 'Indumentaria para el inicio de temporada Sub-13.', ['submitted', 'approved', 'preparing', 'delivered']],
            ['partially_delivered', 'high', 'Camisetas para Sub-15 (faltan dos talles).', ['submitted', 'approved', 'partially_delivered']],
            ['submitted', 'low', 'Buzos para el plantel de Sub-17.', ['submitted']],
            ['rejected', 'normal', 'Pedido duplicado de camisetas.', ['submitted', 'rejected']],
        ];

        foreach ($apparelOrders as $i => [$final, $priority, $description, $history]) {
            $orderId = $this->createOrder(
                type: 'apparel', requestedBy: $profe, categoryId: $this->cat[$i % 3], status: $final,
                priority: $priority, description: $description, history: $history, approver: $coord,
                year: $year, daysAgo: 30 - $i * 6,
            );

            // Ítems: jugadores de la categoría del pedido (mismo rango que en la plantilla).
            $offset = ($i % 3) * 6;
            foreach (array_slice($this->player, $offset, 3) as $n => $playerId) {
                $this->t('apparel_order_items')->insert([
                    'order_id' => $orderId, 'player_id' => $playerId,
                    'apparel_item_id' => $this->apparel[$n % count($this->apparel)],
                    'size' => ['10', 'M', 'L'][$n % 3], 'shirt_number' => $n + 1, 'alias' => null,
                    'quantity' => 1, 'delivered_quantity' => $final === 'delivered' ? 1 : ($final === 'partially_delivered' && $n === 0 ? 1 : 0),
                    'notes' => $n === 2 ? 'Talle a confirmar con la familia.' : null,
                ]);
            }
        }

        $materialOrders = [
            ['delivered', 'normal', 'Reposición de pelotas para la semana.', ['submitted', 'approved', 'delivered'], 0],
            ['preparing', 'urgent', 'Botiquín para la Cancha 1.', ['submitted', 'approved', 'preparing'], 3],
        ];

        foreach ($materialOrders as [$final, $priority, $description, $history, $materialIndex]) {
            $orderId = $this->createOrder(
                type: 'material', requestedBy: $utilero, categoryId: null, status: $final,
                priority: $priority, description: $description, history: $history, approver: $coord,
                year: $year, daysAgo: 12,
            );

            $this->t('material_order_items')->insert([
                'order_id' => $orderId, 'material_id' => $this->material[$materialIndex],
                'quantity' => 6, 'delivered_quantity' => $final === 'delivered' ? 6 : 0,
                'notes' => null,
            ]);
        }
    }

    /** @param  list<string>  $history  estados por los que pasó, en orden */
    private function createOrder(string $type, int $requestedBy, ?int $categoryId, string $status, string $priority, string $description, array $history, int $approver, int $year, int $daysAgo): int
    {
        $number = Order::generateOrderNumber($year);
        $created = $this->now->copy()->subDays($daysAgo);
        $approved = in_array('approved', $history, true) ? $created->copy()->addDay() : null;

        $orderId = $this->t('orders')->insertGetId([
            'order_number' => $number, 'type' => $type, 'requested_by' => $requestedBy, 'category_id' => $categoryId,
            'status' => $status, 'priority' => $priority, 'description' => $description,
            'approved_by' => $approved ? $approver : null, 'approved_at' => $approved,
            'delivered_at' => $status === 'delivered' ? $created->copy()->addDays(5) : null,
            'deleted_at' => null,
        ] + ['created_at' => $created, 'updated_at' => $created]);

        $previous = null;
        foreach ($history as $step => $state) {
            $changedBy = $state === 'submitted' ? $requestedBy : $approver;
            $this->t('order_status_history')->insert([
                'order_id' => $orderId, 'changed_by' => $changedBy, 'old_status' => $previous, 'new_status' => $state,
                'comment' => $state === 'rejected' ? 'Ya existe un pedido igual para la misma categoría.' : null,
                'created_at' => $created->copy()->addHours($step),
            ]);
            $previous = $state;
        }

        return $orderId;
    }

    private function maintenance(): void
    {
        $maintenance = $this->club['mantenimiento@sportiva.com'];
        $reporter = $this->club['coordinador@sportiva.com'];

        $this->repair[] = $this->t('repair_requests')->insertGetId([
            'facility_id' => $this->facilityIds[0], 'component_id' => $this->component[0], 'reported_by' => $reporter, 'assigned_to' => $maintenance,
            'title' => 'Red del arco norte rota', 'description' => 'Desgarro de unos 40 cm en la parte baja de la red.', 'priority' => 'high',
            'status' => 'in_progress', 'reported_at' => $this->now->copy()->subDays(3), 'resolved_at' => null, 'deleted_at' => null,
        ] + $this->ts());
        $this->repair[] = $this->t('repair_requests')->insertGetId([
            'facility_id' => $this->facilityIds[1], 'component_id' => $this->component[2], 'reported_by' => $reporter, 'assigned_to' => $maintenance,
            'title' => 'Ducha 3 sin agua caliente', 'description' => 'La canilla de la ducha 3 no sale agua caliente.', 'priority' => 'normal',
            'status' => 'resolved', 'reported_at' => $this->now->copy()->subDays(8), 'resolved_at' => $this->now->copy()->subDays(6), 'deleted_at' => null,
        ] + $this->ts());
        $this->repair[] = $this->t('repair_requests')->insertGetId([
            'facility_id' => $this->facilityIds[0], 'component_id' => $this->component[1], 'reported_by' => $maintenance, 'assigned_to' => null,
            'title' => 'Revisar foco de la torre de luz', 'description' => 'Uno de los focos parpadea.', 'priority' => 'low',
            'status' => 'open', 'reported_at' => $this->now->copy()->subDay(), 'resolved_at' => null, 'deleted_at' => null,
        ] + $this->ts());

        $this->t('maintenance_records')->insert([
            'repair_request_id' => $this->repair[1], 'facility_id' => $this->facilityIds[1], 'performed_by' => $maintenance,
            'performed_at' => $this->now->copy()->subDays(6), 'description' => 'Se cambió la canilla y se probó el agua caliente.', 'cost' => 18500.00,
        ] + $this->ts());
        $this->t('maintenance_records')->insert([
            'repair_request_id' => $this->repair[0], 'facility_id' => $this->facilityIds[0], 'performed_by' => $maintenance,
            'performed_at' => $this->now->copy()->subDay(), 'description' => 'Se pidió presupuesto de red nueva al proveedor.', 'cost' => null,
        ] + $this->ts());
    }

    private function announcements(): void
    {
        $author = $this->club['coordinador@sportiva.com'];
        $sentAt = $this->now->copy()->subDays(4);

        $sent = $this->t('announcements')->insertGetId([
            'subject' => 'Cambio de horario del entrenamiento del jueves',
            'body' => "Hola familias:\n\nEl entrenamiento del jueves de Sub-15 se mueve a las 17:30 en el Parque Deportivo Norte.\n\nGracias.",
            'audience' => json_encode(['type' => 'category', 'category_ids' => [$this->cat[1]]]),
            'status' => 'sent', 'created_by' => $author, 'sent_by' => $author, 'sent_at' => $sentAt,
            'recipients_count' => 6, 'sent_count' => 5, 'failed_count' => 1, 'deleted_at' => null,
        ] + $this->ts());

        $i = 0;
        foreach (array_slice($this->player, 6, 6) as $n => $playerId) {
            $contact = $this->t('player_contacts')->where('player_id', $playerId)->where('is_primary', true)->first();
            $this->t('announcement_recipients')->insert([
                'announcement_id' => $sent,
                'email' => $contact->email ?? 'familia'.$n.'@example.com',
                'name' => 'Familia de '.$this->t('players')->where('id', $playerId)->value('first_name'),
                'sources' => json_encode([['type' => 'category', 'id' => $this->cat[1]]]),
                'status' => $n === 4 ? 'failed' : 'sent',
                'error' => $n === 4 ? 'Dirección rebotada por el proveedor.' : null,
                'sent_at' => $n === 4 ? null : $sentAt,
            ] + $this->ts());
            $i++;
        }

        $this->t('announcements')->insert([
            'subject' => 'Recordatorio: traer la autorización firmada',
            'body' => 'Borrador: recordar a las familias que traigan la autorización firmada antes del torneo.',
            'audience' => json_encode(['type' => 'club']),
            'status' => 'draft', 'created_by' => $author, 'sent_by' => null, 'sent_at' => null,
            'recipients_count' => 0, 'sent_count' => 0, 'failed_count' => 0, 'deleted_at' => null,
        ] + $this->ts());
    }

    private function notificationsAndAlerts(): void
    {
        $coord = $this->club['coordinador@sportiva.com'];
        $profe = $this->club['profe@sportiva.com'];

        $this->t('notifications')->insert([
            ['club_user_id' => $profe, 'type' => 'callup_pending', 'title' => 'Faltan respuestas a la convocatoria', 'body' => 'Dos convocados todavía no respondieron al partido del próximo sábado.', 'data' => json_encode(['match_id' => $this->match[0]]), 'read_at' => null] + $this->ts(),
            ['club_user_id' => $profe, 'type' => 'attendance_drop', 'title' => 'Asistencia baja: Santino Correa', 'body' => 'Faltó a los últimos dos entrenamientos.', 'data' => json_encode(['player_id' => $this->player[5]]), 'read_at' => $this->now->copy()->subDay()] + $this->ts(),
            ['club_user_id' => $coord, 'type' => 'requirement_expiring', 'title' => 'Apto médico por vencer', 'body' => 'El apto de Julieta Navarro vence en 30 días.', 'data' => json_encode(['player_id' => $this->player[8]]), 'read_at' => null] + $this->ts(),
            ['club_user_id' => $this->club['mantenimiento@sportiva.com'], 'type' => 'repair_assigned', 'title' => 'Te asignaron una reparación', 'body' => 'Red del arco norte rota.', 'data' => json_encode(['repair_request_id' => $this->repair[0]]), 'read_at' => null] + $this->ts(),
            ['club_user_id' => $this->club['utilero@sportiva.com'], 'type' => 'order_approved', 'title' => 'Pedido aprobado', 'body' => 'El pedido de materiales fue aprobado y está en preparación.', 'data' => json_encode([]), 'read_at' => $this->now->copy()->subDays(2)] + $this->ts(),
        ]);

        $this->t('alert_records')->insert([
            ['alert_type' => 'attendance_drop', 'subject_type' => 'player', 'subject_id' => $this->player[5], 'dedup_key' => 'attendance_drop:player:'.$this->player[5], 'notified_at' => $this->now->copy()->subDays(2), 'resolved_at' => null] + $this->ts(),
            ['alert_type' => 'registration_closing', 'subject_type' => 'competition', 'subject_id' => $this->competition[0], 'dedup_key' => 'registration_closing:competition:'.$this->competition[0], 'notified_at' => $this->now->copy()->subDay(), 'resolved_at' => null] + $this->ts(),
        ]);
    }

    private function auditLog(): void
    {
        $admin = $this->club['admin.club@sportiva.com'];
        $coord = $this->club['coordinador@sportiva.com'];

        $this->t('audit_logs')->insert([
            ['club_user_id' => $admin, 'action' => 'club_user.roles_updated', 'entity_type' => 'club_user', 'entity_id' => $coord, 'metadata' => json_encode(['name' => 'Diego Herrera', 'roles' => ['from' => ['Profe'], 'to' => ['Coordinador deportivo']]]), 'ip_address' => '127.0.0.1', 'created_at' => $this->now->copy()->subDays(9)],
            ['club_user_id' => $coord, 'action' => 'order.approved', 'entity_type' => 'order', 'entity_id' => 1, 'metadata' => json_encode(['number' => '0001', 'status' => ['to' => 'approved']]), 'ip_address' => '127.0.0.1', 'created_at' => $this->now->copy()->subDays(25)],
            ['club_user_id' => $coord, 'action' => 'player.deleted', 'entity_type' => 'player', 'entity_id' => 99, 'metadata' => json_encode(['name' => 'Jugador dado de baja']), 'ip_address' => '127.0.0.1', 'created_at' => $this->now->copy()->subDays(4)],
            ['club_user_id' => $admin, 'action' => 'announcement.sent', 'entity_type' => 'announcement', 'entity_id' => 1, 'metadata' => json_encode(['subject' => 'Cambio de horario del entrenamiento del jueves', 'recipients' => 6]), 'ip_address' => '127.0.0.1', 'created_at' => $this->now->copy()->subDays(4)],
        ]);
    }
}
