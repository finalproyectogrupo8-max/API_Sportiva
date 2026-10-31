<?php

namespace App\Club\Eligibility;

use App\Models\Club\Category;
use App\Models\Club\CompetitionRequirement;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerRequirement;
use Illuminate\Support\Collection;

/**
 * Calcula quién está habilitado (TIZ-32). A un jugador le aplican los requisitos de las
 * competencias de sus categorías activas; los "solo menores" no aplican si ya cumplió 18. Está
 * habilitado si tiene aprobados y vigentes todos los obligatorios que le aplican.
 */
class EligibilityCalculator
{
    public const EXPIRING_DAYS = 30;

    /** @return Collection<int, CompetitionRequirement> */
    public function requirementsForCategories(array $categoryIds): Collection
    {
        return CompetitionRequirement::query()
            ->whereHas('competition', fn ($query) => $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)))
            ->with('competition.categories')
            ->orderBy('competition_id')->orderBy('sort')->orderBy('id')
            ->get();
    }

    /** @return list<PlayerEligibility> */
    public function forCategory(Category $category): array
    {
        $memberships = PlayerCategoryMembership::query()
            ->where('category_id', $category->id)
            ->where('status', PlayerCategoryMembership::STATUS_ACTIVE)
            ->whereHas('player')
            ->with('player')
            ->get();
        $requirements = $this->requirementsForCategories([$category->id]);
        $rows = $this->rows($memberships->pluck('player_id')->all(), $requirements->pluck('id')->all());

        return $memberships
            ->sortBy(fn (PlayerCategoryMembership $m) => [$m->player->last_name, $m->player->first_name])
            ->map(fn (PlayerCategoryMembership $m) => $this->evaluate($m->player, $requirements, $rows, $m->shirt_number))
            ->values()
            ->all();
    }

    /** Los requisitos de todas las categorías activas del jugador. */
    public function forPlayer(Player $player): PlayerEligibility
    {
        $categoryIds = $player->activeMemberships()->pluck('category_id')->all();
        $requirements = $this->requirementsForCategories($categoryIds);

        return $this->evaluate($player, $requirements, $this->rows([$player->id], $requirements->pluck('id')->all()));
    }

    public function summary(Category $category): CategoryEligibility
    {
        $players = $this->forCategory($category);
        $deadline = $this->requirementsForCategories([$category->id])
            ->pluck('competition.registration_deadline')->filter()->sort()
            ->first(fn ($date) => $date->gte(today()));

        return new CategoryEligibility(
            $category->id,
            $category->name,
            count($players),
            count(array_filter($players, fn (PlayerEligibility $p) => $p->is_eligible)),
            $deadline?->toDateString(),
            $deadline ? (int) today()->diffInDays($deadline, false) : null,
            array_sum(array_map(fn (PlayerEligibility $p) => count(array_filter($p->requirements, fn (RequirementStatus $r) => $r->expiring_soon)), $players)),
        );
    }

    /** Si el requisito le aplica al jugador. */
    public function applies(CompetitionRequirement $requirement, Player $player): bool
    {
        return ! $requirement->minors_only || $player->birth_date === null || $player->birth_date->age < 18;
    }

    /** @return Collection<string, PlayerRequirement> clave "<player>-<requisito>" */
    private function rows(array $playerIds, array $requirementIds): Collection
    {
        return PlayerRequirement::query()
            ->whereIn('player_id', $playerIds ?: [0])
            ->whereIn('competition_requirement_id', $requirementIds ?: [0])
            ->get()
            ->keyBy(fn (PlayerRequirement $row) => $row->player_id.'-'.$row->competition_requirement_id);
    }

    /**
     * @param  Collection<int, CompetitionRequirement>  $requirements
     * @param  Collection<string, PlayerRequirement>  $rows
     */
    private function evaluate(Player $player, Collection $requirements, Collection $rows, ?int $shirtNumber = null): PlayerEligibility
    {
        $statuses = [];
        $missing = 0;

        foreach ($requirements as $requirement) {
            $row = $rows->get($player->id.'-'.$requirement->id);
            $applies = $this->applies($requirement, $player);
            $status = ! $applies ? 'not_applicable' : ($row?->effectiveStatus() ?? PlayerRequirement::PENDING);

            if ($applies && $requirement->is_required && $status !== PlayerRequirement::APPROVED) {
                $missing++;
            }

            $statuses[] = new RequirementStatus(
                $requirement->id,
                $requirement->name,
                $requirement->competition_id,
                $requirement->competition->name,
                $status,
                $requirement->is_required,
                $requirement->requires_file,
                $row?->expires_at?->toDateString(),
                $status === PlayerRequirement::APPROVED && $row?->expires_at !== null && $row->expires_at->lte(today()->addDays(self::EXPIRING_DAYS)),
                $row?->document_id,
                $status === PlayerRequirement::REJECTED ? $row?->rejection_reason : null,
            );
        }

        return new PlayerEligibility(
            $player->id,
            trim($player->first_name.' '.$player->last_name),
            $shirtNumber,
            $missing === 0,
            $missing,
            $statuses,
        );
    }
}
