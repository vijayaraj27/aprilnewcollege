<?php

namespace App\Models\Web;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfrastructureItem extends Model
{
    use HasFactory;

    protected $table = 'department_infrastructure_items';

    protected $fillable = [
        'language_id',
        'department_id',
        'type',
        'title',
        'description',
        'image',
        'sort_order',
        'status',
    ];

    public const TYPES = [
        'buildings' => 'Buildings',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? 'Infrastructure Item';
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $publicPath = public_path('uploads/infrastructure-items/' . $this->image);
        $rootPath = base_path('uploads/infrastructure-items/' . $this->image);
        $legacyPublic = public_path('uploads/infrastructures/' . $this->image);
        $legacyRoot = base_path('uploads/infrastructures/' . $this->image);

        if (is_file($publicPath) || is_file($rootPath)) {
            return asset('uploads/infrastructure-items/' . $this->image);
        }

        if (is_file($legacyPublic) || is_file($legacyRoot)) {
            return asset('uploads/infrastructures/' . $this->image);
        }

        return asset('uploads/infrastructure-items/' . $this->image);
    }
}
