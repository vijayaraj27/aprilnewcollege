<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagazineItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_magazine_items';

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
        'magazines' => 'Magazines',
    ];

    public const YEAR_TYPES = [
        'magazines',
    ];

    public const SECTION_TYPE_MAP = [
        'magazines' => 'magazines',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
