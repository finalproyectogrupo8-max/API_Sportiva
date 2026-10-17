<?php

namespace App\Policies\Club;

use App\Models\Club\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('categories.view');
    }

    public function create(User $user): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return method_exists($user, 'hasClubPermission') && $user->hasClubPermission('categories.delete');
    }
}
