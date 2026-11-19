<?php

namespace App\Models\Web;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacultyMember extends Model
{
    use HasFactory;

    protected $table = 'department_faculty_members';

    protected $fillable = [
        'language_id',
        'department_id',
        'type',
        'name',
        'designation',
        'qualification',
        'email',
        'au_fin',
        'aicte_id',
        'profile_url',
        'photo',
        'sort_order',
        'status',
    ];

    public const TYPES = [
        'teaching' => 'Teaching Staff',
        'non-teaching' => 'Non-Teaching Staff',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? 'Faculty Member';
    }

    public function photoUrl(): ?string
    {
        if (empty($this->photo)) {
            return asset('uploads/images/default-faculty-avatar.svg');
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        $publicPath = public_path('uploads/faculty-members/' . $this->photo);
        $rootPath = base_path('uploads/faculty-members/' . $this->photo);
        if (!is_file($publicPath) && !is_file($rootPath)) {
            return asset('uploads/images/default-faculty-avatar.svg');
        }

        return asset('uploads/faculty-members/' . $this->photo);
    }
}
