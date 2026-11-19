<?php

namespace App\Models\Web;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeOutcome extends Model
{
    use HasFactory;

    protected $table = 'department_programme_outcomes';

    protected $fillable = [
        'language_id',
        'department_id',
        'type',
        'title',
        'description',
        'sort_order',
        'status',
    ];

    public const TYPES = [
        'peo' => 'Programme Educational Objectives',
        'po'  => 'Programme Outcomes',
        'pso' => 'Programme Specific Outcomes',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? 'Programme Outcome';
    }

    /**
     * Remove leading PEO1:/PO1:/PSO1: style labels from text.
     */
    public static function stripTypePrefix(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return preg_replace('/^(PEO|PSO|PO)\s*\d+\s*:\s*/i', '', $text, 1);
    }
}
