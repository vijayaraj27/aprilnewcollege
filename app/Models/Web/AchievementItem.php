<?php

namespace App\Models\Web;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementItem extends Model
{
    use HasFactory;

    protected $table = 'department_achievement_items';

    protected $fillable = [
        'language_id',
        'department_id',
        'type',
        'year',
        'title',
        'description',
        'image',
        'student_name',
        'event_nature',
        'event_name',
        'period_date',
        'organized_by',
        'awards',
        'year_of_student',
        'project_name',
        'supervisor',
        'result_status',
        'sort_order',
        'status',
    ];

    public const TYPES = [
        'staff' => 'Staff Achievements',
        'student' => 'Student Achievements',
        'student-table' => 'Student Achievements Table',
        'student-appreciation' => 'Student Achievements Appreciation',
    ];

    public const SECTION_TYPE_MAP = [
        'staff-achivements' => 'staff',
        'student-achivements' => 'student',
        'student-achivements-table' => 'student-table',
        'student-achivements-appreciation' => 'student-appreciation',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? 'Achievement Item';
    }

    public static function yearSortKey(?string $year): int
    {
        if ($year === null || $year === '') {
            return 0;
        }
        if (preg_match('/(\d{4})/', $year, $match)) {
            return (int) $match[1];
        }

        return 0;
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $publicPath = public_path('uploads/achievement-items/' . $this->image);
        $rootPath = base_path('uploads/achievement-items/' . $this->image);
        $legacyPublic = public_path('uploads/achievements/' . $this->image);
        $legacyRoot = base_path('uploads/achievements/' . $this->image);

        if (is_file($publicPath) || is_file($rootPath)) {
            return asset('uploads/achievement-items/' . $this->image);
        }

        if (is_file($legacyPublic) || is_file($legacyRoot)) {
            return asset('uploads/achievements/' . $this->image);
        }

        return asset('uploads/achievement-items/' . $this->image);
    }

    public function displayLabel(): string
    {
        return match ($this->type) {
            'staff' => (string) ($this->description ?? $this->title ?? ''),
            'student' => (string) ($this->title ?: strip_tags((string) $this->description)),
            'student-table' => (string) ($this->student_name ?: $this->event_name ?: ''),
            'student-appreciation' => (string) ($this->student_name ?: $this->event_name ?: ''),
            default => (string) ($this->title ?? $this->description ?? ''),
        };
    }
}
