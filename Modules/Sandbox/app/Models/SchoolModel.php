<?php

namespace Modules\Sandbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\SchoolModelFactory;

class SchoolModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'school_name_th',
        'school_name_en',
        'ministry',
        'department',
        'area',
        'school_sizes',
        'founding_date',
        'school_course_type',
        'course_attachment',
        'original_filename',
        'principal_prefix_code',
        'principal_name_thai',
        'principal_middle_name_thai',
        'principal_lastname_thai',
        'deputy_principal_prefix_code',
        'deputy_principal_name_thai',
        'deputy_principal_middle_name_thai',
        'deputy_principal_lastname_thai',
        'house_id',
        'village_no',
        'road',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'phone',
        'fax',
        'email',
        'website',
        'latitude',
        'longitude',
        'student_amount',
        'disadvantaged_student_amount',
        'teacher_amount',
    ];

    protected $table = 'school_data';

    protected $primaryKey = 'school_id';

    protected $casts = [
        'school_course_type' => 'array',
    ];

    public function setSchoolCourseTypeAttribute($value)
    {
        $this->attributes['school_course_type'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getSchoolCourseTypeAttribute($value)
    {
        return json_decode($value, true);
    }

    public function schoolInnovations()
    {
        return $this->hasMany(InnovationsModel::class, 'school_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'school_id', 'school_id');
    }
    // protected static function newFactory(): SchoolModelFactory
    // {
    //     // return SchoolModelFactory::new();
    // }
}
