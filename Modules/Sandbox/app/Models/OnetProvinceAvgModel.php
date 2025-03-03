<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\OnetProvinceAvgModelFactory;

class OnetProvinceAvgModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'onet_province_avg';
    // protected static function newFactory(): OnetProvinceAvgModelFactory
    // {
    //     // return OnetProvinceAvgModelFactory::new();
    // }
}
