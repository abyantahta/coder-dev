<?php

namespace App\Policies;

use App\Models\Line;
use App\Models\User;

class LinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function view(User $user, Line $line): bool
    {
        return $user->managesMasterData();
    }

    public function create(User $user): bool
    {
        return $user->managesMasterData();
    }

    public function update(User $user, Line $line): bool
    {
        return $user->managesMasterData();
    }

    public function delete(User $user, Line $line): bool
    {
        return $user->managesMasterData();
    }

    public function sync(User $user): bool
    {
        return $user->managesMasterData();
    }
}
