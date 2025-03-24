<?php

namespace Modules\Sandbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sandbox\Models\SchoolModel;

class ParticipantModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'participants';
    
    protected $primaryKey = 'participant_id';
    
    public $incrementing = false;
    
    protected $keyType = 'string';

    protected $fillable = [
        'participant_id',
        'cooperation_school_id',
        'user_id',
        'participant_name',
        'participant_type_code',
        'contact_name',
        'contact_phone',
        'contact_mobile_phone',
        'contact_email',
        'contact_organization_position',
        'cooperation_start_date',
        'cooperation_end_date',
        'cooperation_status_code',
        'cooperation_activity',
        'cooperation_level_code',
        'cooperation_attachment_url',
    ];

    protected $casts = [
        'cooperation_start_date' => 'date',
        'cooperation_end_date' => 'date',
        'cooperation_attachment_url' => 'array',
    ];

    // พื้นที่นวัตกรรมการศึกษา (Sandbox)
    const INNOVATION_AREAS = [
        '21' => 'ระยอง',
        '33' => 'ศรีสะเกษ',
        '50' => 'เชียงใหม่',
        '71' => 'กาญจนบุรี',
        '91' => 'สตูล',
        '94' => 'ปัตตานี',
        '95' => 'ยะลา',
        '96' => 'นราธิวาส',
    ];

    // ประเภทภาค
    const SECTOR_TYPES = [
        '1' => 'ภาครัฐ',
        '2' => 'ภาคเอกชน',
    ];

    // ประเภทผู้เข้ามามีส่วนร่วม
    const PARTICIPANT_TYPES = [
        '01' => 'บุคคล',
        '02' => 'หน่วยงานรัฐ/รัฐวิสาหกิจ',
        '03' => 'บริษัทเอกชน',
        '04' => 'มูลนิธิ',
        '05' => 'สมาคม',
        '06' => 'องค์กรต่างประเทศ',
    ];

    // สถานะการมีส่วนร่วม
    const COOPERATION_STATUS = [
        '01' => 'ยังมีส่วนร่วม',
        '02' => 'สิ้นสุดการมีส่วนร่วม',
        '03' => 'ไม่มีการเข้ามามีส่วนร่วม',
    ];

    // ระดับการมีส่วนร่วม
    const COOPERATION_LEVELS = [
        '01' => 'ให้ข้อมูล',
        '02' => 'ให้คำปรึกษา',
        '03' => 'มีส่วนร่วมบางส่วน',
        '04' => 'ทำงานร่วมกัน',
        '05' => 'สนับสนุนงบประมาณ',
        '06' => 'สนับสนุนสื่อ/อุปกรณ์',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Get the school that owns the participant.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolModel::class, 'cooperation_school_id', 'school_id');
    }

    /**
     * Get the user who created the participant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * แปลงวันที่เป็นรูปแบบ dd/MM/yyyy
     */
    public function getFormattedStartDateAttribute()
    {
        return $this->cooperation_start_date ? $this->cooperation_start_date->format('d/m/Y') : null;
    }

    /**
     * แปลงวันที่เป็นรูปแบบ dd/MM/yyyy
     */
    public function getFormattedEndDateAttribute()
    {
        return $this->cooperation_end_date ? $this->cooperation_end_date->format('d/m/Y') : null;
    }

    /**
     * ดึงชื่อประเภทภาค
     */
    public function getSectorTypeNameAttribute()
    {
        $sectorType = substr($this->participant_id, 0, 1);
        return self::SECTOR_TYPES[$sectorType] ?? 'ไม่ระบุ';
    }

    /**
     * ดึงชื่อพื้นที่นวัตกรรม
     */
    public function getInnovationAreaNameAttribute()
    {
        $areaCode = substr($this->participant_id, 1, 2);
        return self::INNOVATION_AREAS[$areaCode] ?? 'ไม่ระบุ';
    }

    /**
     * ดึงชื่อประเภทผู้เข้ามามีส่วนร่วม
     */
    public function getParticipantTypeNameAttribute()
    {
        return self::PARTICIPANT_TYPES[$this->participant_type_code] ?? 'ไม่ระบุ';
    }

    /**
     * ดึงชื่อสถานะการมีส่วนร่วม
     */
    public function getCooperationStatusNameAttribute()
    {
        return self::COOPERATION_STATUS[$this->cooperation_status_code] ?? 'ไม่ระบุ';
    }

    /**
     * ดึกชื่อระดับการมีส่วนร่วม
     */
    public function getCooperationLevelNameAttribute()
    {
        return self::COOPERATION_LEVELS[$this->cooperation_level_code] ?? 'ไม่ระบุ';
    }
}
