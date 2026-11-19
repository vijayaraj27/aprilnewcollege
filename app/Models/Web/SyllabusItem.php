<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SyllabusItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_syllabus_items';

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
        'syllabus' => 'Syllabus',
        'regulation' => 'Regulation',
        'question-bank' => 'Question Bank',
    ];

    public const YEAR_TYPES = [];

    public const SECTION_TYPE_MAP = [
        'syllabus' => 'syllabus',
        'regulation' => 'regulation',
        'questionBank' => 'question-bank',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    /**
     * Sort regulations/syllabus items: Program Type (UG, PG, …), then Regulation Type
     * (newer year first when present), then sort_order, then id.
     */
    public static function sortForListing(Collection $items): Collection
    {
        return $items->sort(function ($a, $b) {
            $programCmp = self::programTypeSortKey($a->metaGet('programType'))
                <=> self::programTypeSortKey($b->metaGet('programType'));
            if ($programCmp !== 0) {
                return $programCmp;
            }

            $programLabelCmp = strcasecmp(
                (string) $a->metaGet('programType', ''),
                (string) $b->metaGet('programType', '')
            );
            if ($programLabelCmp !== 0) {
                return $programLabelCmp;
            }

            $regCmp = self::regulationTypeSortKey($b->metaGet('regulationType'))
                <=> self::regulationTypeSortKey($a->metaGet('regulationType'));
            if ($regCmp !== 0) {
                return $regCmp;
            }

            $regLabelCmp = strcasecmp(
                (string) $a->metaGet('regulationType', ''),
                (string) $b->metaGet('regulationType', '')
            );
            if ($regLabelCmp !== 0) {
                return $regLabelCmp;
            }

            $sortCmp = ((int) $a->sort_order) <=> ((int) $b->sort_order);
            if ($sortCmp !== 0) {
                return $sortCmp;
            }

            return ((int) $a->id) <=> ((int) $b->id);
        })->values();
    }

    public static function programTypeSortKey(?string $programType): int
    {
        $value = strtoupper(trim((string) $programType));
        $value = str_replace(['.', ' '], '', $value);

        return match (true) {
            $value === 'UG' || str_starts_with($value, 'UG') || str_contains($value, 'UNDERGRAD') => 1,
            $value === 'PG' || str_starts_with($value, 'PG') || str_contains($value, 'POSTGRAD') => 2,
            default => 50,
        };
    }

    public static function regulationTypeSortKey(?string $regulationType): int
    {
        if ($regulationType === null || $regulationType === '') {
            return 0;
        }
        if (preg_match('/(\d{4})/', $regulationType, $match)) {
            return (int) $match[1];
        }

        return 0;
    }
}
