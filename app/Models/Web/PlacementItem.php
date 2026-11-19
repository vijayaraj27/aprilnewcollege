<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacementItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_placement_items';

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
        'student-placed' => 'Student Placed',
    ];

    public const YEAR_TYPES = [
        'student-placed',
    ];

    public const SECTION_TYPE_MAP = [
        'student-placed' => 'student-placed',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
