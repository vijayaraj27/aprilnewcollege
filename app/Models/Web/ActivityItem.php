<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_activity_items';

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
        'department-activity' => 'Department Activity',
        'student-participation' => 'Student Participation',
        'inter-institute-prize' => 'Inter Institute Events Winning Prize',
        'industrial-visit' => 'Industrial Visit',
    ];

    public const YEAR_TYPES = [
        'department-activity',
        'student-participation',
        'inter-institute-prize',
    ];

    public const SECTION_TYPE_MAP = [
        'departmentActivity' => 'department-activity',
        'studentParticipation' => 'student-participation',
        'interInstituteEventsWinningPrize' => 'inter-institute-prize',
        'industrialVisit' => 'industrial-visit',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
