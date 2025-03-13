<?php

namespace Modules\Sandbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sandbox\Database\Factories\InnovationsModelFactory;

class InnovationsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'innovationID',
        'education_year',
        'semester',
        'school_id',
        'inno_type_id',
        'inno_name',
        'inno_description',
        'tags',
        'attachments',
        'original_filename',
        'video_url',
        'user_id',
    ];

    protected $table = 'innovations';
    protected $primaryKey = 'innovationID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'tags' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(SchoolModel::class, 'school_id', 'school_id');
    }

    public function innovationType()
    {
        return $this->belongsTo(InnovationTypesModel::class, 'inno_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // protected static function newFactory(): InnovationsModelFactory
    // {
    //     // return InnovationsModelFactory::new();
    // }
}
