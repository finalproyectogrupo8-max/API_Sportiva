<?php

namespace App\Club\Callups;

use App\Club\Eligibility\EligibilityCalculator;
use App\Club\Eligibility\PlayerEligibility;
use App\Club\Eligibility\RequirementStatus;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Armar, publicar y mostrar la convocatoria de un partido (TIZ-33). */
class CallupService
{
    public function __construct(
        private EligibilityCalculator $eligibility,
        private CallupMessages $messages,
    ) {}

    public function sheet(GameMatch $match, Tenant $tenant): CallupSheet
    {
        $match->loadMissing('category');
        $callups = $match->callups()->with('player.contacts')->get()
            ->sortBy(fn (MatchCallup $c) => [$c->shirt_number ?? 999, $c->player->last_name])->values();
        $published = $match->callup_published_at !== null;

        $rows = $callups->map(fn (MatchCallup $c) => new CallupRow(
            $c->player_id,
            trim($c->player->first_name.' '.$c->player->last_name),
            $c->shirt_number,
            $c->response,
            $c->response_note,
            $c->responded_at?->toIso8601String(),
            $this->messages->personalUrl($c),
            $published ? $this->messages->whatsappUrl($c, $match) : null,
        ))->all();

        $calledUp = $callups->pluck('player_id')->all();
        $squad = array_map(fn (PlayerEligibility $p) => new SquadOption(
            $p->player_id,
            $p->player_name,
            $p->shirt_number,
            $p->is_eligible,
            $this->missingNames($p),
            in_array($p->player_id, $calledUp, true),
        ), $this->eligibility->forCategory($match->category));

        return new CallupSheet(
            $match->id,
            $match->callup_published_at?->toIso8601String(),
            $match->updated_after_publish,
            $this->messages->publicUrl($match),
            $published ? $this->messages->groupMessage($match, $tenant) : null,
            $callups->where('response', MatchCallup::GOING)->count(),
            $callups->where('response', MatchCallup::NOT_GOING)->count(),
            $callups->where('response', MatchCallup::PENDING)->count(),
            $rows,
            $squad,
        );
    }

    /**
     * Reemplaza la lista de convocados. Solo jugadores del plantel y habilitados; el número sale del
     * plantel si no se manda y no se repite. Los que siguen conservan su respuesta.
     *
     * @param  list<array{player_id: int, shirt_number?: int|null}>  $players
     */
    public function update(GameMatch $match, Tenant $tenant, array $players): void
    {
        $match->loadMissing('category');
        $squad = collect($this->eligibility->forCategory($match->category))->keyBy('player_id');
        $numbers = PlayerCategoryMembership::query()->where('category_id', $match->category_id)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)->pluck('shirt_number', 'player_id');

        $errors = [];
        $used = [];
        $rows = [];
        foreach (array_values($players) as $i => $item) {
            /** @var PlayerEligibility|null $player */
            $player = $squad->get($item['player_id']);
            if (! $player) {
                $errors["players.{$i}.player_id"] = 'El jugador no está en el plantel de la categoría.';

                continue;
            }
            if (! $player->is_eligible) {
                $errors["players.{$i}.player_id"] = "{$player->player_name} no está habilitado: falta ".implode(', ', $this->missingNames($player)).'.';

                continue;
            }

            $number = array_key_exists('shirt_number', $item) ? $item['shirt_number'] : $numbers->get($item['player_id']);
            if ($number !== null && in_array($number, $used, true)) {
                $errors["players.{$i}.shirt_number"] = "El número {$number} está repetido.";
            }
            $used[] = $number;
            $rows[$item['player_id']] = $number;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::connection('tenant')->transaction(function () use ($match, $tenant, $rows) {
            $match->callups()->whereNotIn('player_id', array_keys($rows) ?: [0])->delete();

            foreach ($rows as $playerId => $number) {
                $callup = $match->callups()->firstOrNew(['player_id' => $playerId]);
                $callup->shirt_number = $number;
                $callup->token ??= $tenant->slug.'.'.Str::random(40);
                $callup->response ??= MatchCallup::PENDING;
                $callup->save();
            }
        });
    }

    public function publish(GameMatch $match, Tenant $tenant): void
    {
        if (! $match->callups()->exists()) {
            throw ValidationException::withMessages(['players' => 'Convocá al menos un jugador antes de publicar.']);
        }

        $match->forceFill([
            'callup_published_at' => now(),
            'public_token' => $match->public_token ?? $tenant->slug.'.'.Str::random(40),
            'updated_after_publish' => false,
        ])->save();
    }

    /** @return list<string> */
    private function missingNames(PlayerEligibility $player): array
    {
        return array_values(array_map(
            fn (RequirementStatus $r) => $r->name,
            array_filter($player->requirements, fn (RequirementStatus $r) => $r->is_required && ! in_array($r->status, ['approved', 'not_applicable'], true)),
        ));
    }
}
