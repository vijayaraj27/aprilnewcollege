<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_event_items';

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
        'events' => 'Event',
    ];

    public const YEAR_TYPES = [];

    public const SECTION_TYPE_MAP = [
        'eventDetails' => 'events',
        'event-list' => 'events',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
