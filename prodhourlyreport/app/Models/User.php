<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Lines assigned to this user. Meaningful for leaders/group heads —
     * unit head / GM / IT Super User see every line regardless of assignment.
     */
    public function lines(): BelongsToMany
    {
        return $this->belongsToMany(Line::class, 'line_user');
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function isSuperUser(): bool
    {
        return $this->role->isSuperUser();
    }

    public function managesMasterData(): bool
    {
        return $this->role->managesMasterData();
    }

    public function accessesAllLines(): bool
    {
        return $this->role->accessesAllLines();
    }

    public function canSubmitProduction(): bool
    {
        return $this->role->submitsProduction();
    }

    public function overseesProduction(): bool
    {
        return $this->role->overseesProduction();
    }

    public function canAccessLine(int $lineId): bool
    {
        if ($this->accessesAllLines()) {
            return true;
        }

        return $this->lines()->whereKey($lineId)->exists();
    }

    /**
     * Line IDs this user is scoped to, or null when they can see every line.
     *
     * @return array<int>|null
     */
    public function visibleLineIds(): ?array
    {
        if ($this->accessesAllLines()) {
            return null;
        }

        return $this->lines()->pluck('lines.id')->all();
    }
}
