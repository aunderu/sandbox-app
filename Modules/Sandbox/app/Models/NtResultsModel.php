<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\NtResultsModelFactory;

class NtResultsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'nt_results';

    // protected static function newFactory(): NtResultsModelFactory
    // {
    //     // return NtResultsModelFactory::new();
    // }
}
