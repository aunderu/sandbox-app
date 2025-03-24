<?php

namespace Modules\Dashboard\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sandbox\Models\ParticipantModel;

class ParticipantPolicy
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
        // Officer, School Admin และ Admin สามารถดูรายการได้
        return in_array($user->role, [
            UserRole::OFFICER,
            UserRole::SCHOOLADMIN,
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถดูรายละเอียดของรายการนั้นๆ ได้หรือไม่
     */
    public function view(User $user, ParticipantModel $participant): bool
    {
        // Officer, Admin สามารถดูได้ทุกรายการ
        if (
            $user->role === UserRole::OFFICER ||
            $user->role === UserRole::SUPERADMIN
        ) {
            return true;
        }

        // School Admin สามารถดูเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $participant->cooperation_school_id;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถสร้างรายการใหม่ได้หรือไม่
     */
    public function create(User $user): bool
    {
        // Officer, School Admin และ Admin สามารถสร้างรายการได้
        return in_array($user->role, [
            UserRole::OFFICER,
            UserRole::SCHOOLADMIN,
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถแก้ไขรายการนั้นๆ ได้หรือไม่
     */
    public function update(User $user, ParticipantModel $participant): bool
    {
        // Officer และ Admin สามารถแก้ไขได้ทุกรายการ
        if (
            $user->role === UserRole::OFFICER ||
            $user->role === UserRole::SUPERADMIN
        ) {
            return true;
        }

        // School Admin สามารถแก้ไขเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $participant->cooperation_school_id;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบรายการนั้นๆ ได้หรือไม่
     */
    public function delete(User $user, ParticipantModel $participant): bool
    {
        // Admin สามารถลบได้ทุกรายการ
        if (
            $user->role === UserRole::SUPERADMIN
        ) {
            return true;
        }

        // School Admin สามารถลบเฉพาะรายการของโรงเรียนตนเอง
        if ($user->role === UserRole::SCHOOLADMIN) {
            return $user->school_id === $participant->cooperation_school_id;
        }

        // Officer ไม่สามารถลบรายการได้
        return false;
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบหลายรายการพร้อมกันได้หรือไม่
     */
    public function deleteAny(User $user): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถลบหลายรายการพร้อมกันได้
        return in_array($user->role, [
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถกู้คืนรายการที่ลบไปแล้วได้หรือไม่ (ถ้ามี soft delete)
     */
    public function restore(User $user, ParticipantModel $participant): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถกู้คืนรายการได้
        return in_array($user->role, [
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถกู้คืนหลายรายการพร้อมกันได้หรือไม่
     */
    public function restoreAny(User $user): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถกู้คืนหลายรายการพร้อมกันได้
        return in_array($user->role, [
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบรายการอย่างถาวรได้หรือไม่ (ถ้ามี soft delete)
     */
    public function forceDelete(User $user, ParticipantModel $participant): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถลบรายการอย่างถาวรได้
        return in_array($user->role, [
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถลบหลายรายการอย่างถาวรพร้อมกันได้หรือไม่
     */
    public function forceDeleteAny(User $user): bool
    {
        // เฉพาะ Admin เท่านั้นที่สามารถลบหลายรายการอย่างถาวรพร้อมกันได้
        return in_array($user->role, [
            UserRole::SUPERADMIN,
        ]);
    }
}
