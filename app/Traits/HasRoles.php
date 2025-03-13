<?php

namespace App\Traits;

use App\Enums\UserRole;

trait HasRoles
{
    /**
     * Check if the user is a super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    /**
     * Check if the user is a school admin.
     *
     * @return bool
     */
    public function isSchoolAdmin(): bool
    {
        return $this->role === UserRole::SCHOOLADMIN;
    }

    /**
     * Check if the user is an officer.
     *
     * @return bool
     */
    public function isOfficer(): bool
    {
        return $this->role === UserRole::OFFICER;
    }

    /**
     * Check if user has admin privileges.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isSchoolAdmin() || $this->isOfficer();
    }

    /**
     * Check if the user is a regular user.
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    /**
     * Check if the user has the specified role.
     *
     * @param UserRole $role
     * @return bool
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }
}