<?php

namespace Modules\Dashboard\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sandbox\Models\BasicSubjectAssessmentModel;

class BasicSubjectAssessmentPolicy
{
    use HandlesAuthorization;

    /**
     * ตรวจสอบการเข้าถึงทั่วไป
     */
    public function before(User $user, string $ability): bool|null
    {
        // Super Admin มีสิทธิ์ทำได้ทั้งหมด
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return null; // ใช้ permission ปกติ
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูรายการทั้งหมดได้หรือไม่
     */
    public function viewAny(User $user): bool
    {
        // Officer และ School Admin สามารถดูรายการได้
        return in_array($user->role, [
            UserRole::OFFICER,
            UserRole::SCHOOLADMIN,
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูรายละเอียดของรายการนั้นๆ ได้หรือไม่
     */
    public function view(User $user, BasicSubjectAssessmentModel $assessment): bool
    {
        // Officer สามารถดูได้ทุกรายการ
        if (
            $user->role === UserRole::OFFICER ||
            $user->role === UserRole::SUPERADMIN
        ) {
            return true;
        }

        // School Admin สามารถดูเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $assessment->school_id;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถสร้างรายการใหม่ได้หรือไม่
     */
    public function create(User $user): bool
    {
        // เฉพาะ School Admin เท่านั้นที่สามารถสร้างรายการได้
        return $user->role === UserRole::SCHOOLADMIN ||
            $user->role === UserRole::SUPERADMIN;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถแก้ไขรายการนั้นๆ ได้หรือไม่
     */
    public function update(User $user, BasicSubjectAssessmentModel $assessment): bool
    {
        // School Admin สามารถแก้ไขเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $assessment->school_id;
        } else if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบรายการนั้นๆ ได้หรือไม่
     */
    public function delete(User $user, BasicSubjectAssessmentModel $assessment): bool
    {
        // School Admin สามารถลบเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $assessment->school_id;
        } else if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบหลายรายการพร้อมกันได้หรือไม่
     */
    public function deleteAny(User $user): bool
    {
        // เฉพาะ Super Admin เท่านั้นที่สามารถลบหลายรายการพร้อมกันได้
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถกู้คืนรายการที่ลบไปแล้วได้หรือไม่ (ถ้ามี soft delete)
     */
    public function restore(User $user, BasicSubjectAssessmentModel $assessment): bool
    {
        // เฉพาะ Super Admin เท่านั้นที่สามารถกู้คืนรายการได้
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบรายการอย่างถาวรได้หรือไม่ (ถ้ามี soft delete)
     */
    public function forceDelete(User $user, BasicSubjectAssessmentModel $assessment): bool
    {
        // เฉพาะ Super Admin เท่านั้นที่สามารถลบรายการอย่างถาวรได้
        return $user->role === UserRole::SUPERADMIN;
    }
}
