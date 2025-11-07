<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Category $category): bool
    {
        return $user->merchant_id === $category->merchant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->merchant_id === $category->merchant_id;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->merchant_id === $category->merchant_id;
    }
}