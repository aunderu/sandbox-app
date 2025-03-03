<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\OnetNationalAvgModelFactory;

class OnetNationalAvgModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'onet_national_avg';

    // protected static function newFactory(): OnetNationalAvgModelFactory
    // {
    //     // return OnetNationalAvgModelFactory::new();
    // }
}
