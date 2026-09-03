<?php

namespace App\Enums;

enum UserRole: string
{
    case Leader = 'leader';
    case GroupHead = 'group_head';
    case UnitHead = 'unit_head';
    case Gm = 'gm';
    case ItSuperUser = 'it_super_user';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Leader',
            self::GroupHead => 'Group Head',
            self::UnitHead => 'Unit Head',
            self::Gm => 'General Manager',
            self::ItSuperUser => 'IT Super User',
        };
    }

    /**
     * Hierarchy rank, used for "at least this level" checks.
     * Leader < Group Head < Unit Head < GM < IT Super User.
     */
    public function level(): int
    {
        return match ($this) {
            self::Leader => 1,
            self::GroupHead => 2,
            self::UnitHead => 3,
            self::Gm => 4,
            self::ItSuperUser => 5,
        };
    }

    public function isAtLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Full access: every menu, every policy, every line.
     */
    public function isSuperUser(): bool
    {
        return $this === self::ItSuperUser;
    }

    /**
     * Master data + user admin (Lines/Models/Products/Users/QAD sync).
     * GM and IT Super User only — Unit Head is operational, not admin.
     */
    public function managesMasterData(): bool
    {
        return $this->isSuperUser() || $this->isAtLeast(self::Gm);
    }

    /**
     * Unit Head is a Group Head with every line in scope (no assignment needed).
     * GM / IT Super User also see every line.
     */
    public function accessesAllLines(): bool
    {
        return $this->isSuperUser() || $this->isAtLeast(self::UnitHead);
    }

    /**
     * Leaders enter production on assigned lines.
     * Unit Head can enter on any line (group-head scope, all lines).
     * IT Super User can do everything.
     */
    public function submitsProduction(): bool
    {
        return $this === self::Leader
            || $this === self::UnitHead
            || $this->isSuperUser();
    }

    /**
     * Can edit/delete production logs on lines they can access (not only own entries).
     */
    public function overseesProduction(): bool
    {
        return $this->accessesAllLines();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
