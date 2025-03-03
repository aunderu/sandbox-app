namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolModel extends Model
{
    protected $casts = [
        'school_course_type' => 'array',
    ];

    public function setSchoolCourseTypeAttribute($value)
    {
        $this->attributes['school_course_type'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getSchoolCourseTypeAttribute($value)
    {
        return json_decode($value, true);
    }
}
