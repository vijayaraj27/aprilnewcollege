<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_research_items';

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
        'phd' => 'PhD Holders',
        'anna-university' => 'Anna University Supervisors',
        'pursuing-phd' => 'Candidates Pursuing PhD',
        'faculties-pursuing-phd' => 'Faculties Pursuing PhD',
        'phd-awarded' => 'PhD Awarded',
        'supervisor' => 'Supervisors',
        'funds' => 'Research Funds',
        'value-added-group' => 'Value Added Group',
    ];

    public const YEAR_TYPES = [
        'funds',
    ];

    public const SECTION_TYPE_MAP = [
        'phd' => 'phd',
        'anna-university' => 'anna-university',
        'pursuing-phd' => 'pursuing-phd',
        'faculties-pursuing-phd' => 'faculties-pursuing-phd',
        'phd-awarded' => 'phd-awarded',
        'supervisor' => 'supervisor',
        'funds' => 'funds',
        'value-added-group' => 'value-added-group',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
