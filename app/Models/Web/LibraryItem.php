<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_library_items';

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
        'record' => 'Library Records',
    ];

    public const YEAR_TYPES = [];

    public const SECTION_TYPE_MAP = [
        'record' => 'record',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
