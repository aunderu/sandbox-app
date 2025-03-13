<?php

namespace Modules\Dashboard\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sandbox\Models\StudentNumberModel;

class StudentNumberPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // ผู้ใช้ทั้งหมดสามารถดูรายการได้
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentNumberModel $studentNumber): bool
    {
        // สำหรับผู้ดูแลระบบ
        if ($user->isSuperAdmin()) {
            return true;
        }

        // สำหรับผู้ดูแลโรงเรียน
        if ($user->isSchoolAdmin()) {
            return $user->school_id === $studentNumber->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // ผู้ดูแลระบบหรือผู้ดูแลโรงเรียนสามารถสร้างรายการได้
        return $user->isSuperAdmin() || $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StudentNumberModel $studentNumber): bool
    {
        // สำหรับผู้ดูแลระบบ
        if ($user->isSuperAdmin()) {
            return true;
        }

        // สำหรับผู้ดูแลโรงเรียน
        if ($user->isSchoolAdmin()) {
            return $user->school_id === $studentNumber->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StudentNumberModel $studentNumber): bool
    {
        // สำหรับผู้ดูแลระบบ
        if ($user->isSuperAdmin()) {
            return true;
        }

        // สำหรับผู้ดูแลโรงเรียน
        if ($user->isSchoolAdmin()) {
            return $user->school_id === $studentNumber->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถลบหลายรายการได้พร้อมกัน
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StudentNumberModel $studentNumber): bool
    {
        // สำหรับผู้ดูแลระบบ
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StudentNumberModel $studentNumber): bool
    {
        // สำหรับผู้ดูแลระบบ
        return $user->isSuperAdmin();
    }
}
