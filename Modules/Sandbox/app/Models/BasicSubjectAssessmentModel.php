<?php

namespace Modules\Sandbox\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BasicSubjectAssessmentModel extends Model
{
    protected $table = 'basic_subject_assessments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'school_id',
        'education_year',
        'thai_score',
        'math_score',
        'science_score',
        'english_score',
    ];
    
    // ความสัมพันธ์กับโรงเรียน
    public function school()
    {
        return $this->belongsTo(SchoolModel::class, 'school_id', 'school_id');
    }
}
