<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->managesMasterData();
    }

    public function sync(User $user): bool
    {
        return $user->managesMasterData();
    }
}
