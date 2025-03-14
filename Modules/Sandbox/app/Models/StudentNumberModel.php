<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\StudentNumberModelFactory;

class StudentNumberModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'grade_id',
        'education_year',
        'male_count',
        'female_count',
    ];

    protected $table = 'student_number';

    public function school()
    {
        return $this->belongsTo(SchoolModel::class, 'school_id', 'school_id');
    }

    public function grade()
    {
        return $this->belongsTo(GradeLevelsModel::class, 'grade_id', 'id');
    }
}
