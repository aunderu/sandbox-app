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
    protected $fillable = [];

    protected $table = 'student_number';
    // protected static function newFactory(): StudentNumberModelFactory
    // {
    //     // return StudentNumberModelFactory::new();
    // }
}
