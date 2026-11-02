<?php

namespace App\Club\Announcements;

use App\Models\Club\CategoryCoach;
use App\Models\Club\ClubUser;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerContact;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Convierte la audiencia de un comunicado (TIZ-24) en direcciones de email sin repetir. La usan la
 * vista previa y el envío, así cuentan lo mismo. Corre con la conexión `tenant` apuntando al club.
 *
 * Audiencia: `categories` [{category_id, players, tutors, coaches}], `players` {player_ids, players,
 * tutors}, `role_slugs` y `club_user_ids`. El jugador recibe en su contacto `self`; los tutores, en
 * sus demás contactos con email.
 */
class AudienceResolver
{
    /** @var array<string, array{email: string, name: string, sources: list<array{type: string, id: int}>}> */
    private array $recipients = [];

    /** @var array<string, string> Personas incluidas sin email, por clave única. */
    private array $withoutEmail = [];

    /**
     * @param  array<string, mixed>  $audience
     * @return array{recipients: list<array{email: string, name: string, sources: list<array{type: string, id: int}>}>, without_email: list<string>}
     */
    public function resolve(array $audience): array
    {
        $this->recipients = [];
        $this->withoutEmail = [];

        foreach ($audience['categories'] ?? [] as $group) {
            $playerIds = PlayerCategoryMembership::query()
                ->where('category_id', $group['category_id'])
                ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
                ->pluck('player_id')->all();
            $this->addPlayers($playerIds, (bool) ($group['players'] ?? false), (bool) ($group['tutors'] ?? false));

            if ($group['coaches'] ?? false) {
                $this->addCoaches($group['category_id']);
            }
        }

        if ($individual = $audience['players'] ?? null) {
            $this->addPlayers($individual['player_ids'] ?? [], (bool) ($individual['players'] ?? true), (bool) ($individual['tutors'] ?? false));
        }

        $this->addClubUsers($audience['role_slugs'] ?? [], $audience['club_user_ids'] ?? []);

        return ['recipients' => array_values($this->recipients), 'without_email' => array_values($this->withoutEmail)];
    }

    /** @param  list<int>  $playerIds */
    private function addPlayers(array $playerIds, bool $players, bool $tutors): void
    {
        if ($playerIds === [] || (! $players && ! $tutors)) {
            return;
        }

        Player::query()->whereKey($playerIds)->with('contacts')->get()->each(function (Player $player) use ($players, $tutors) {
            $name = trim($player->first_name.' '.$player->last_name);

            if ($players) {
                $self = $player->contacts->first(fn (PlayerContact $c) => $c->relationship === 'self' && filled($c->email));
                $self ? $this->add($self->email, $name, 'player_contact', $self->id) : $this->missing("player:{$player->id}", $name);
            }

            if ($tutors) {
                $guardians = $player->contacts->filter(fn (PlayerContact $c) => $c->relationship !== 'self' && filled($c->email));
                $guardians->each(fn (PlayerContact $c) => $this->add($c->email, $c->name, 'player_contact', $c->id));
                if ($guardians->isEmpty()) {
                    $this->missing("tutors:{$player->id}", "Tutores de {$name}");
                }
            }
        });
    }

    private function addCoaches(int $categoryId): void
    {
        CategoryCoach::query()->current()->where('category_id', $categoryId)->with('coach')->get()
            ->pluck('coach')->filter()->unique('id')
            ->each(function ($coach) {
                $name = trim($coach->first_name.' '.$coach->last_name);
                filled($coach->email) ? $this->add($coach->email, $name, 'coach', $coach->id) : $this->missing("coach:{$coach->id}", $name);
            });
    }

    /**
     * @param  list<string>  $roleSlugs
     * @param  list<int>  $clubUserIds
     */
    private function addClubUsers(array $roleSlugs, array $clubUserIds): void
    {
        if ($roleSlugs === [] && $clubUserIds === []) {
            return;
        }

        $clubUsers = ClubUser::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q
                ->whereKey($clubUserIds)
                ->when($roleSlugs !== [], fn ($q) => $q->orWhereHas('roles', fn ($r) => $r->whereIn('slug', $roleSlugs))))
            ->get();

        // El email del staff vive en la base central.
        $emails = User::query()->whereKey($clubUsers->pluck('global_user_id'))->pluck('email', 'id');

        $clubUsers->each(function (ClubUser $user) use ($emails) {
            $email = $emails->get($user->global_user_id);
            $email ? $this->add($email, $user->display_name, 'club_user', $user->id) : $this->missing("club_user:{$user->id}", $user->display_name);
        });
    }

    private function add(string $email, string $name, string $type, int $id): void
    {
        $key = mb_strtolower(trim($email));
        $this->recipients[$key] ??= ['email' => $key, 'name' => $name, 'sources' => []];
        $this->recipients[$key]['sources'][] = ['type' => $type, 'id' => $id];
    }

    private function missing(string $key, string $name): void
    {
        $this->withoutEmail[$key] = $name;
    }

    /** Muestra para la vista previa: hasta 10 nombres de destinatarios. */
    public static function sample(array $recipients): array
    {
        return Collection::make($recipients)->take(10)->pluck('name')->all();
    }
}
