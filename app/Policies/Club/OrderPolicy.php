<?php

namespace App\Policies\Club;

use App\Models\Club\Order;
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

    private function isScoped(User $user, ?string $type, string $action): bool
    {
        $access = $user->clubAccess();

        if ($access->isOwnScope("orders.{$action}")) {
            return true;
        }

        if ($type === Order::TYPE_MATERIAL) {
            return $access->isOwnScope("material_orders.{$action}");
        }

        if ($type === Order::TYPE_APPAREL) {
            return $access->isOwnScope("apparel_orders.{$action}");
        }

        return $access->isOwnScope("apparel_orders.{$action}")
            || $access->isOwnScope("material_orders.{$action}");
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

        if ($this->isScoped($user, $order->type, 'view')) {
            $access = $user->clubAccess();
            $inOwnCategory = $order->category_id && in_array($order->category_id, $access->ownCategoryIds(), true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inOwnCategory || $isRequestedBySelf;
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

        if ($this->isScoped($user, $order->type, 'update')) {
            $access = $user->clubAccess();
            $inOwnCategory = $order->category_id && in_array($order->category_id, $access->ownCategoryIds(), true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inOwnCategory || $isRequestedBySelf;
        }

        return true;
    }

    public function delete(User $user, Order $order): bool
    {
        if (! $this->hasTypePermission($user, $order->type, 'delete')) {
            return false;
        }

        if ($this->isScoped($user, $order->type, 'delete')) {
            $access = $user->clubAccess();
            $inOwnCategory = $order->category_id && in_array($order->category_id, $access->ownCategoryIds(), true);
            $isRequestedBySelf = $order->requested_by === $user->currentClubUser()?->id;

            return $inOwnCategory || $isRequestedBySelf;
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
