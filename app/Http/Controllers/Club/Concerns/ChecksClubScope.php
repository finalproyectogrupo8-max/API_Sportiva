<?php

namespace App\Http\Controllers\Club\Concerns;

use App\Club\Access\ClubAccess;
use App\Models\Club\Category;
use App\Models\Club\Player;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Permiso del club y alcance "sus categorías". Fuera del alcance se responde 404, no 403, para no
 * revelar que el registro existe (docs/roles-y-permisos.md §4).
 */
trait ChecksClubScope
{
    protected function authorizeClub(string $permission): void
    {
        Gate::allowIf(fn (User $user) => $user->hasClubPermission($permission));
    }

    protected function access(): ClubAccess
    {
        return request()->user()->clubAccess();
    }

    protected function ensureCategoryInScope(Category $category, string $permission): void
    {
        $access = $this->access();
        $scoped = $access->scopedCategoryIds($permission);

        abort_if($scoped !== null && ! in_array($category->id, $scoped, true), 404);
    }

    protected function ensurePlayerVisible(Player $player, string $permission = 'players.view'): void
    {
        $visible = Player::query()->visibleTo($this->access(), $permission)->whereKey($player->getKey())->exists();

        abort_unless($visible, 404);
    }
}
