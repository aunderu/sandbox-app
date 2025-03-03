<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\OnetResultsModelFactory;

class OnetResultsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'onet_results';
    // protected static function newFactory(): OnetResultsModelFactory
    // {
    //     // return OnetResultsModelFactory::new();
    // }
}
