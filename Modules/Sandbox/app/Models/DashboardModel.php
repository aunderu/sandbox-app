<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\DashboardModelFactory;

class DashboardModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): DashboardModelFactory
    // {
    //     // return DashboardModelFactory::new();
    // }
}
