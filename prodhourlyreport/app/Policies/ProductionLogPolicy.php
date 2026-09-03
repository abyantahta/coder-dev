<?php

namespace App\Policies;

use App\Models\ProductionLog;
use App\Models\User;

class ProductionLogPolicy
{
    /**
     * Query results are scoped per-role in the controller; this just gates
     * the endpoint itself.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductionLog $productionLog): bool
    {
        return $user->canAccessLine($productionLog->line_id);
    }

    /**
     * Leaders (assigned lines) and Unit Head / IT Super User (all lines).
     */
    public function create(User $user, ?int $lineId = null): bool
    {
        if (! $user->canSubmitProduction()) {
            return false;
        }

        return $lineId === null || $user->canAccessLine($lineId);
    }

    public function update(User $user, ProductionLog $productionLog): bool
    {
        if ($user->overseesProduction() && $user->canAccessLine($productionLog->line_id)) {
            return true;
        }

        return $user->is($productionLog->user);
    }

    public function delete(User $user, ProductionLog $productionLog): bool
    {
        if ($user->overseesProduction() && $user->canAccessLine($productionLog->line_id)) {
            return true;
        }

        return $user->is($productionLog->user);
    }
}
