<?php

namespace Modules\Dashboard\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sandbox\Models\InnovationsModel;

class InnovationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $users): bool
    {
        return $users->isSuperAdmin()
            || $users->isSchoolAdmin()
            || $users->isOfficer()
            || $users->isUser();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $users, InnovationsModel $innovation): bool
    {
        return $users->isSuperAdmin()
            || ($users->isSchoolAdmin() && $users->school_id === $innovation->school_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $users): bool
    {
        return $users->isSuperAdmin()
            || $users->isSchoolAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $users, InnovationsModel $innovation): bool
    {
        return $users->isSuperAdmin()
            || ($users->isSchoolAdmin() && $users->school_id === $innovation->school_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $users, InnovationsModel $innovation): bool
    {
        return $users->isSuperAdmin()
            || ($users->isSchoolAdmin() && $users->school_id === $innovation->school_id);
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $users): bool
    {
        return $users->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $users): bool
    {
        return $users->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $users): bool
    {
        return $users->isSuperAdmin();
    }
}
