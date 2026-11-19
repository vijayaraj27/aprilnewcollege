<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_course_items';

    protected $fillable = [
        'language_id',
        'department_id',
        'type',
        'year',
        'title',
        'description',
        'file',
        'image',
        'meta',
        'sort_order',
        'status',
    ];

    public const TYPES = [
        'courses' => 'Course Materials',
    ];

    public const YEAR_TYPES = [];

    public const SECTION_TYPE_MAP = [
        'courses' => 'courses',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
