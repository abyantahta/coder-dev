<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, User $model): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, User $model): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->managesMasterData() && $user->isNot($model);
    }
}
