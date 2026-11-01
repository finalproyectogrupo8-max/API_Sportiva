<?php

namespace App\Http\Controllers\Club;

use App\Club\Eligibility\EligibilityCalculator;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\CategoryEligibilityResource;
use App\Http\Resources\Club\PlayerEligibilityResource;
use App\Models\Club\Category;
use App\Models\Club\CompetitionRequirement;
use App\Models\Club\Document;
use App\Models\Club\Player;
use App\Models\Club\PlayerRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Habilitación de temporada (TIZ-32): qué le falta a cada jugador y quién puede jugar. Presentar
 * un papel es de quien lo carga (profe, coordinador); aprobarlo o rechazarlo, de quien tiene
 * `player_requirements.approve`.
 */
class EligibilityController extends Controller
{
    use ChecksClubScope;

    public function __construct(private EligibilityCalculator $calculator) {}

    /** Resumen del club: una fila por categoría activa con competencias. */
    public function index(): AnonymousResourceCollection
    {
        $this->authorizeClub('player_requirements.view');
        $access = $this->access();
        $scoped = $access->scopedCategoryIds('player_requirements.view');

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('competitions')
            ->when($scoped !== null, fn ($query) => $query->whereIn('id', $scoped ?: [0]))
            ->orderBy('name')
            ->get();

        return CategoryEligibilityResource::collection($categories->map(fn (Category $category) => $this->calculator->summary($category))->all());
    }

    /** La grilla de una categoría: jugadores × requisitos. */
    public function category(Category $category): AnonymousResourceCollection
    {
        $this->authorizeClub('player_requirements.view');
        $this->ensureCategoryInScope($category, 'player_requirements.view');

        return PlayerEligibilityResource::collection($this->calculator->forCategory($category));
    }

    public function player(Player $player): PlayerEligibilityResource
    {
        $this->authorizeClub('player_requirements.view');
        $this->ensurePlayerVisible($player, 'player_requirements.view');

        return PlayerEligibilityResource::make($this->calculator->forPlayer($player));
    }

    /** Presentar un papel. Si el requisito pide archivo, `document_id` (subido con TIZ-29) es obligatorio. */
    public function submit(Request $request, Player $player, CompetitionRequirement $requirement): PlayerEligibilityResource
    {
        $this->authorizeClub('player_requirements.update');
        $this->ensurePlayerVisible($player, 'player_requirements.update');
        $data = $request->validate(['document_id' => ['nullable', 'integer']]);
        $this->ensureApplies($requirement, $player);
        $documentId = $this->documentFor($player, $requirement, $data['document_id'] ?? null);

        PlayerRequirement::query()->updateOrCreate(
            ['player_id' => $player->id, 'competition_requirement_id' => $requirement->id],
            ['status' => PlayerRequirement::SUBMITTED, 'document_id' => $documentId, 'submitted_at' => now(), 'rejection_reason' => null],
        );

        return PlayerEligibilityResource::make($this->calculator->forPlayer($player));
    }

    /**
     * Aprobar. Vence a los `validity_months` del requisito, salvo que se mande `expires_at` (la fecha
     * del apto). Se puede aprobar sin presentar antes si el club recibió el papel en mano.
     */
    public function approve(Request $request, Player $player, CompetitionRequirement $requirement): PlayerEligibilityResource
    {
        $this->authorizeClub('player_requirements.approve');
        $this->ensurePlayerVisible($player, 'player_requirements.view');
        $data = $request->validate([
            'document_id' => ['nullable', 'integer'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);
        $this->ensureApplies($requirement, $player);

        $row = PlayerRequirement::query()->firstOrNew(['player_id' => $player->id, 'competition_requirement_id' => $requirement->id]);
        $documentId = $this->documentFor($player, $requirement, $data['document_id'] ?? $row->document_id);
        $expiresAt = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->toDateString()
            : ($requirement->validity_months ? today()->addMonths($requirement->validity_months)->toDateString() : null);

        $row->fill([
            'status' => PlayerRequirement::APPROVED,
            'document_id' => $documentId,
            'submitted_at' => $row->submitted_at ?? now(),
            'expires_at' => $expiresAt,
            'reviewed_by' => $request->user()->currentClubUser()?->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        return PlayerEligibilityResource::make($this->calculator->forPlayer($player));
    }

    public function reject(Request $request, Player $player, CompetitionRequirement $requirement): PlayerEligibilityResource
    {
        $this->authorizeClub('player_requirements.approve');
        $this->ensurePlayerVisible($player, 'player_requirements.view');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'Contá por qué se rechaza.']);
        $this->ensureApplies($requirement, $player);

        PlayerRequirement::query()->updateOrCreate(
            ['player_id' => $player->id, 'competition_requirement_id' => $requirement->id],
            ['status' => PlayerRequirement::REJECTED, 'rejection_reason' => $data['reason'], 'reviewed_by' => $request->user()->currentClubUser()?->id, 'reviewed_at' => now(), 'expires_at' => null],
        );

        return PlayerEligibilityResource::make($this->calculator->forPlayer($player));
    }

    /** El requisito tiene que ser de una competencia de las categorías del jugador y aplicarle. */
    private function ensureApplies(CompetitionRequirement $requirement, Player $player): void
    {
        $categoryIds = $player->activeMemberships()->pluck('category_id')->all();
        $ofPlayer = $this->calculator->requirementsForCategories($categoryIds)->contains('id', $requirement->id);
        abort_unless($ofPlayer, 404);

        if (! $this->calculator->applies($requirement, $player)) {
            throw ValidationException::withMessages(['requirement' => 'Este requisito es solo para menores de 18.']);
        }
    }

    private function documentFor(Player $player, CompetitionRequirement $requirement, ?int $documentId): ?int
    {
        if ($documentId === null) {
            if ($requirement->requires_file) {
                throw ValidationException::withMessages(['document_id' => "\"{$requirement->name}\" pide un archivo: subilo y mandá su document_id."]);
            }

            return null;
        }

        $valid = Document::query()->whereKey($documentId)->where('documentable_type', 'player')->where('documentable_id', $player->id)->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['document_id' => 'El documento no es de este jugador.']);
        }

        return $documentId;
    }
}
