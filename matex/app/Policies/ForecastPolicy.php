<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Forecast;
use App\Models\User;

class ForecastPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Purchasing, UserRole::Admin, UserRole::SupplierRm);
    }

    public function view(User $user, Forecast $forecast): bool
    {
        if ($user->hasRole(UserRole::Purchasing, UserRole::Admin)) {
            return true;
        }

        return $user->hasRole(UserRole::SupplierRm)
            && $forecast->supplier_rm_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Purchasing, UserRole::Admin);
    }

    public function update(User $user, Forecast $forecast): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Forecast $forecast): bool
    {
        return $this->create($user);
    }

    public function download(User $user, Forecast $forecast): bool
    {
        return $this->view($user, $forecast);
    }
}
