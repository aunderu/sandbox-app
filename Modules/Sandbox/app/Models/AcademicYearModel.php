<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\AcademicYearModelFactory;

class AcademicYearModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'grade_levels';

    // protected static function newFactory(): AcademicYearModelFactory
    // {
    //     // return AcademicYearModelFactory::new();
    // }
}
