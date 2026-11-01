<?php

namespace App\Policies\Club;

use App\Models\Club\Order;
use App\Models\Club\Permission;
use App\Models\User;

class OrderPolicy
{
    private function hasTypePermission(User $user, ?string $type, string $action): bool
    {
        if ($user->hasClubPermission("orders.{$action}")) {
            return true;
        }

        if ($type === Order::TYPE_MATERIAL) {
            return $user->hasClubPermission("material_orders.{$action}");
        }

        if ($type === Order::TYPE_APPAREL) {
            return $user->hasClubPermission("apparel_orders.{$action}");
        }

        return $user->hasClubPermission("apparel_orders.{$action}")
            || $user->hasClubPermission("material_orders.{$action}");
    }

    private function scopedCategoryIds(User $user, ?string $type, string $action): ?array
    {
        $access = $user->clubAccess();

        $candidates = match ($type) {
            Order::TYPE_MATERIAL => ["material_orders.{$action}", "orders.{$action}"],
            Order::TYPE_APPAREL => ["apparel_orders.{$action}", "orders.{$action}"],
            default => ["orders.{$action}", "apparel_orders.{$action}", "material_orders.{$action}"],
        };

        foreach ($candidates as $perm) {
            if ($access->has($perm)) {
                $scope = $access->scope($perm);
                if ($scope === Permission::SCOPE_ALL) {
                    return null;
                }

                return $access->scopedCategoryIds($perm);
            }
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasTypePermission($user, null, 'view');
    }

    public function view(User $user, Order $order): bool
    {
        if (! $this->hasTypePermission($user, $order->type, 'view')) {
            return false;
        }

        $scopedCategoryIds = $this->scopedCategoryIds($user, $order->type, 'view');
        if ($scopedCategoryIds !== null) {
            $inScopedCategory = $order->category_id && in_array($order->category_id, $scopedCategoryIds, true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inScopedCategory || $isRequestedBySelf;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasTypePermission($user, null, 'create');
    }

    public function update(User $user, Order $order): bool
    {
        if (! $this->hasTypePermission($user, $order->type, 'update')) {
            return false;
        }

        $scopedCategoryIds = $this->scopedCategoryIds($user, $order->type, 'update');
        if ($scopedCategoryIds !== null) {
            $inScopedCategory = $order->category_id && in_array($order->category_id, $scopedCategoryIds, true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inScopedCategory || $isRequestedBySelf;
        }

        return true;
    }

    public function delete(User $user, Order $order): bool
    {
        if (! $this->hasTypePermission($user, $order->type, 'delete')) {
            return false;
        }

        $scopedCategoryIds = $this->scopedCategoryIds($user, $order->type, 'delete');
        if ($scopedCategoryIds !== null) {
            $inScopedCategory = $order->category_id && in_array($order->category_id, $scopedCategoryIds, true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inScopedCategory || $isRequestedBySelf;
        }

        return true;
    }

    public function submit(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }

    public function approve(User $user, Order $order): bool
    {
        return $this->hasTypePermission($user, $order->type, 'approve');
    }

    public function reject(User $user, Order $order): bool
    {
        return $this->hasTypePermission($user, $order->type, 'approve')
            || $this->hasTypePermission($user, $order->type, 'update');
    }

    public function deliver(User $user, Order $order): bool
    {
        return $this->hasTypePermission($user, $order->type, 'deliver');
    }
}
