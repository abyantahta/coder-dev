<?php

namespace App\Policies;

use App\Models\ProductModel;
use App\Models\User;

class ProductModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, ProductModel $productModel): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, ProductModel $productModel): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, ProductModel $productModel): bool
    {
        return $user->managesMasterData();
    }
}
