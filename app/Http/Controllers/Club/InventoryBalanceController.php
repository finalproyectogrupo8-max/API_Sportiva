<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexInventoryBalanceRequest;
use App\Http\Resources\Club\InventoryBalanceResource;
use App\Models\Club\InventoryBalance;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryBalanceController extends Controller
{
    use ChecksClubScope;

    public function index(IndexInventoryBalanceRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('inventory.view');

        $filters = $request->validated();

        $balances = InventoryBalance::query()
            ->with(['material', 'location'])
            ->when($filters['location_id'] ?? null, fn ($q, $locId) => $q->where('location_id', $locId))
            ->when($filters['material_id'] ?? null, fn ($q, $matId) => $q->where('material_id', $matId))
            ->when($request->boolean('below_minimum'), function ($q) {
                $q->whereHas('material', function ($mq) {
                    $mq->whereColumn('inventory_balances.quantity', '<', 'materials.minimum_stock');
                });
            })
            ->orderBy('location_id')
            ->orderBy('material_id')
            ->paginate($filters['per_page'] ?? 20);

        return InventoryBalanceResource::collection($balances);
    }
}
