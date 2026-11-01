<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\DeliverOrderRequest;
use App\Http\Requests\Club\IndexOrderRequest;
use App\Http\Requests\Club\RejectOrderRequest;
use App\Http\Requests\Club\StoreOrderRequest;
use App\Http\Requests\Club\UpdateOrderRequest;
use App\Http\Resources\Club\OrderResource;
use App\Models\Club\ClubUser;
use App\Models\Club\Order;
use App\Services\Club\InventoryMovementService;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    private function resolveClubUserId(Request $request): int
    {
        $clubUserId = $request->user()->clubAccess()->clubUser?->id
            ?? ClubUser::where('global_user_id', $request->user()->id)->value('id');

        if (! $clubUserId) {
            $clubUser = ClubUser::create([
                'global_user_id' => $request->user()->id,
                'display_name' => $request->user()->name,
                'status' => 'active',
            ]);
            $clubUserId = $clubUser->id;
        }

        return $clubUserId;
    }

    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Order::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = Order::query()
            ->with([
                'category',
                'apparelOrderItems.player',
                'apparelOrderItems.apparelItem',
                'materialOrderItems.material',
                'statusHistory',
            ])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($q, $catId) => $q->where('category_id', $catId))
            ->when($filters['requested_by'] ?? null, fn ($q, $reqBy) => $q->where('requested_by', $reqBy))
            ->when($filters['priority'] ?? null, fn ($q, $prio) => $q->where('priority', $prio));

        $query->visibleTo($request->user()->clubAccess(), 'orders.view');

        $orders = $query->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 20);

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', Order::class);

        $data = $request->validated();
        $clubUserId = $this->resolveClubUserId($request);

        $order = DB::connection('tenant')->transaction(function () use ($data, $clubUserId) {
            $orderNumber = Order::generateOrderNumber();

            $order = Order::create([
                'order_number' => $orderNumber,
                'type' => $data['type'],
                'requested_by' => $clubUserId,
                'category_id' => $data['category_id'] ?? null,
                'status' => Order::STATUS_DRAFT,
                'priority' => $data['priority'] ?? 'normal',
                'description' => $data['description'] ?? null,
            ]);

            if ($data['type'] === Order::TYPE_APPAREL && ! empty($data['apparel_order_items'])) {
                foreach ($data['apparel_order_items'] as $item) {
                    $order->apparelOrderItems()->create([
                        'player_id' => $item['player_id'],
                        'apparel_item_id' => $item['apparel_item_id'],
                        'size' => $item['size'],
                        'shirt_number' => $item['shirt_number'] ?? null,
                        'alias' => $item['alias'] ?? null,
                        'quantity' => $item['quantity'],
                        'delivered_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            } elseif ($data['type'] === Order::TYPE_MATERIAL && ! empty($data['material_order_items'])) {
                foreach ($data['material_order_items'] as $item) {
                    $order->materialOrderItems()->create([
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'delivered_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $order->statusHistory()->create([
                'changed_by' => $clubUserId,
                'old_status' => null,
                'new_status' => Order::STATUS_DRAFT,
                'comment' => 'Creación de borrador',
                'created_at' => now(),
            ]);

            return $order;
        });

        return OrderResource::make($order->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource|JsonResponse
    {
        Gate::authorize('update', $order);

        if ($order->status !== Order::STATUS_DRAFT) {
            return ApiError::conflict('order_not_draft');
        }

        $data = $request->validated();

        $order = DB::connection('tenant')->transaction(function () use ($order, $data) {
            $order->update(array_filter([
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $order->category_id,
                'priority' => $data['priority'] ?? $order->priority,
                'description' => array_key_exists('description', $data) ? $data['description'] : $order->description,
            ], fn ($val) => $val !== null));

            if ($order->type === Order::TYPE_APPAREL && isset($data['apparel_order_items'])) {
                $order->apparelOrderItems()->delete();
                foreach ($data['apparel_order_items'] as $item) {
                    $order->apparelOrderItems()->create([
                        'player_id' => $item['player_id'],
                        'apparel_item_id' => $item['apparel_item_id'],
                        'size' => $item['size'],
                        'shirt_number' => $item['shirt_number'] ?? null,
                        'alias' => $item['alias'] ?? null,
                        'quantity' => $item['quantity'],
                        'delivered_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            } elseif ($order->type === Order::TYPE_MATERIAL && isset($data['material_order_items'])) {
                $order->materialOrderItems()->delete();
                foreach ($data['material_order_items'] as $item) {
                    $order->materialOrderItems()->create([
                        'material_id' => $item['material_id'],
                        'quantity' => $item['quantity'],
                        'delivered_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            return $order;
        });

        return OrderResource::make($order->refresh()->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }

    public function destroy(Order $order): Response|JsonResponse
    {
        Gate::authorize('delete', $order);

        if ($order->status !== Order::STATUS_DRAFT) {
            return ApiError::conflict('order_not_draft');
        }

        $order->delete();

        return response()->noContent();
    }

    public function submit(Request $request, Order $order): OrderResource|JsonResponse
    {
        Gate::authorize('submit', $order);

        if ($order->status !== Order::STATUS_DRAFT) {
            return ApiError::conflict('order_not_draft');
        }

        $itemsCount = $order->type === Order::TYPE_APPAREL
            ? $order->apparelOrderItems()->count()
            : $order->materialOrderItems()->count();

        if ($itemsCount === 0) {
            return response()->json([
                'message' => 'El pedido debe tener al menos una línea para enviarse.',
                'code' => 'order_empty',
            ], 422);
        }

        $clubUserId = $this->resolveClubUserId($request);

        DB::connection('tenant')->transaction(function () use ($order, $clubUserId, $request) {
            $order->recordStatusTransition($clubUserId, Order::STATUS_SUBMITTED, $request->input('comment'));
        });

        return OrderResource::make($order->refresh()->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }

    public function approve(Request $request, Order $order): OrderResource|JsonResponse
    {
        Gate::authorize('approve', $order);

        if (! in_array($order->status, [Order::STATUS_SUBMITTED, 'pending'], true)) {
            if ($order->status === Order::STATUS_APPROVED) {
                return ApiError::conflict('order_already_approved');
            }

            return ApiError::conflict('order_not_submitted');
        }

        $clubUserId = $this->resolveClubUserId($request);

        DB::connection('tenant')->transaction(function () use ($order, $clubUserId, $request) {
            $order->approved_by = $clubUserId;
            $order->approved_at = now();
            $order->recordStatusTransition($clubUserId, Order::STATUS_APPROVED, $request->input('comment'));

            // TODO: Notificar al solicitante de la aprobación del pedido (implementación en tarea de notificaciones).
        });

        return OrderResource::make($order->refresh()->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }

    public function reject(RejectOrderRequest $request, Order $order): OrderResource|JsonResponse
    {
        Gate::authorize('reject', $order);

        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_REJECTED], true)) {
            return ApiError::conflict('invalid_order_transition');
        }

        $clubUserId = $this->resolveClubUserId($request);
        $reason = $request->validated('reason');

        DB::connection('tenant')->transaction(function () use ($order, $clubUserId, $reason) {
            $order->recordStatusTransition($clubUserId, Order::STATUS_REJECTED, $reason);

            // TODO: Notificar al solicitante del rechazo del pedido con el motivo (implementación en tarea de notificaciones).
        });

        return OrderResource::make($order->refresh()->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }

    public function deliveries(DeliverOrderRequest $request, Order $order, InventoryMovementService $inventoryService): OrderResource|JsonResponse
    {
        Gate::authorize('deliver', $order);

        if ($order->status === Order::STATUS_DELIVERED) {
            return ApiError::conflict('order_already_delivered');
        }

        if ($order->status === Order::STATUS_REJECTED) {
            return ApiError::conflict('order_rejected');
        }

        if (! in_array($order->status, [Order::STATUS_APPROVED, Order::STATUS_PARTIALLY_DELIVERED, Order::STATUS_PREPARING], true)) {
            return ApiError::conflict('invalid_order_transition');
        }

        $clubUserId = $this->resolveClubUserId($request);
        $deliveries = $request->input('deliveries');
        $locationId = $request->input('location_id');

        $result = DB::connection('tenant')->transaction(function () use (
            $order,
            $clubUserId,
            $deliveries,
            $locationId,
            $inventoryService,
            $request
        ) {
            if ($order->type === Order::TYPE_APPAREL) {
                if (! empty($deliveries)) {
                    foreach ($deliveries as $del) {
                        $itemId = $del['id'] ?? $del['item_id'] ?? null;
                        $item = $order->apparelOrderItems()->where('id', $itemId)->lockForUpdate()->first();

                        if (! $item) {
                            return response()->json([
                                'message' => 'Línea de pedido no encontrada.',
                                'code' => 'item_not_found',
                            ], 404);
                        }

                        if (isset($del['delivered_quantity'])) {
                            $newDelivered = (int) $del['delivered_quantity'];
                        } else {
                            $qty = (int) ($del['quantity'] ?? 0);
                            $newDelivered = $item->delivered_quantity + $qty;
                        }

                        if ($newDelivered > $item->quantity) {
                            return response()->json([
                                'message' => 'La cantidad entregada no puede superar la cantidad pedida.',
                                'code' => 'exceeds_ordered_quantity',
                                'errors' => ['deliveries' => ['La cantidad entregada supera la cantidad pedida.']],
                            ], 422);
                        }

                        $item->delivered_quantity = $newDelivered;
                        $item->save();
                    }
                } else {
                    foreach ($order->apparelOrderItems()->lockForUpdate()->get() as $item) {
                        $item->delivered_quantity = $item->quantity;
                        $item->save();
                    }
                }

                $totalQty = (int) $order->apparelOrderItems()->sum('quantity');
                $deliveredQty = (int) $order->apparelOrderItems()->sum('delivered_quantity');
            } else {
                if (! empty($deliveries)) {
                    foreach ($deliveries as $del) {
                        $itemId = $del['id'] ?? $del['item_id'] ?? null;
                        $item = $order->materialOrderItems()->where('id', $itemId)->lockForUpdate()->first();

                        if (! $item) {
                            return response()->json([
                                'message' => 'Línea de pedido no encontrada.',
                                'code' => 'item_not_found',
                            ], 404);
                        }

                        if (isset($del['delivered_quantity'])) {
                            $newDelivered = (float) $del['delivered_quantity'];
                            $delta = $newDelivered - (float) $item->delivered_quantity;
                        } else {
                            $delta = (float) ($del['quantity'] ?? 0);
                            $newDelivered = (float) $item->delivered_quantity + $delta;
                        }

                        if ($newDelivered > (float) $item->quantity) {
                            return response()->json([
                                'message' => 'La cantidad entregada no puede superar la cantidad pedida.',
                                'code' => 'exceeds_ordered_quantity',
                                'errors' => ['deliveries' => ['La cantidad entregada supera la cantidad pedida.']],
                            ], 422);
                        }

                        $item->delivered_quantity = $newDelivered;
                        $item->save();

                        if ($locationId && $delta > 0) {
                            $inventoryService->recordMovement([
                                'material_id' => $item->material_id,
                                'location_id' => (int) $locationId,
                                'movement_type' => 'out',
                                'quantity' => $delta,
                                'order_id' => $order->id,
                                'performed_by' => $clubUserId,
                                'reason' => 'Entrega de pedido '.$order->order_number,
                            ]);
                        }
                    }
                } else {
                    foreach ($order->materialOrderItems()->lockForUpdate()->get() as $item) {
                        $delta = (float) $item->quantity - (float) $item->delivered_quantity;
                        $item->delivered_quantity = $item->quantity;
                        $item->save();

                        if ($locationId && $delta > 0) {
                            $inventoryService->recordMovement([
                                'material_id' => $item->material_id,
                                'location_id' => (int) $locationId,
                                'movement_type' => 'out',
                                'quantity' => $delta,
                                'order_id' => $order->id,
                                'performed_by' => $clubUserId,
                                'reason' => 'Entrega de pedido '.$order->order_number,
                            ]);
                        }
                    }
                }

                $totalQty = (float) $order->materialOrderItems()->sum('quantity');
                $deliveredQty = (float) $order->materialOrderItems()->sum('delivered_quantity');
            }

            $isFullyDelivered = $deliveredQty >= $totalQty && $totalQty > 0;
            $newStatus = $isFullyDelivered ? Order::STATUS_DELIVERED : Order::STATUS_PARTIALLY_DELIVERED;

            if ($isFullyDelivered) {
                $order->delivered_at = now();
            }

            $comment = $request->input('comment') ?? ($isFullyDelivered ? 'Entrega completada' : 'Entrega parcial');
            $order->recordStatusTransition($clubUserId, $newStatus, $comment);

            // TODO: Notificar al solicitante de la entrega (implementación en tarea de notificaciones).

            return null;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return OrderResource::make($order->refresh()->load([
            'category',
            'apparelOrderItems.player',
            'apparelOrderItems.apparelItem',
            'materialOrderItems.material',
            'statusHistory',
        ]));
    }
}
