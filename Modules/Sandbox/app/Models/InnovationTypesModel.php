<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\InnovationTypesModelFactory;

class InnovationTypesModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'innovation_types';

    public function innovations()
    {
        return $this->hasMany(InnovationsModel::class, 'inno_type_id');
    }

    // protected static function newFactory(): InnovationTypesModelFactory
    // {
    //     // return InnovationTypesModelFactory::new();
    // }
}
