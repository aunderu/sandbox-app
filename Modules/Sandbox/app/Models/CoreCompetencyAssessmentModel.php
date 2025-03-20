<?php

namespace Modules\Sandbox\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreCompetencyAssessmentModel extends Model
{
    use HasFactory;

    protected $table = 'core_competency_assessments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'school_id',
        'education_year',
        'self_management_score',
        'teamwork_score',
        'high_thinking_score',
        'communication_score',
        'active_citizen_score',
        'sustainable_coexistence_score',
    ];

    /**
     * สร้าง ID อัตโนมัติ
     * 
     * @param string $schoolId รหัสโรงเรียน
     * @return string
     */
    public static function generateCompetencyId($schoolId)
    {
        // ใช้ปีปัจจุบัน (2 ตัวหลังของ พ.ศ.) เช่น 2568 = 68
        $year = substr(Carbon::now()->year + 543, -2);
        
        // หา record ล่าสุดที่มีรูปแบบ SchoolID + Year + RunningNumber
        $latestRecord = self::where('id', 'like', $schoolId . $year . '%')
            ->orderBy('id', 'desc')
            ->first();

        // กำหนดเลขลำดับ
        if ($latestRecord) {
            // ดึงเลขลำดับล่าสุด (4 หลักสุดท้าย) แล้วเพิ่มอีก 1
            $lastSequence = (int) substr($latestRecord->id, -4);
            $newSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // ถ้าไม่มีเลขลำดับก่อนหน้า เริ่มที่ 0001
            $newSequence = '0001';
        }
        
        // สร้าง ID รูปแบบ: SchoolID + Year + RunningNumber (8 + 2 + 4 = 14 หลัก)
        return $schoolId . $year . $newSequence;
    }
    
    // ความสัมพันธ์กับโรงเรียน
    public function school()
    {
        return $this->belongsTo(SchoolModel::class, 'school_id', 'school_id');
    }
}
