<?php

namespace App\Models\Web\Concerns;

trait IsDepartmentContentItem
{
    public function getMetaAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function setMetaAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['meta'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['meta'] = $value;
    }

    public function metaGet(string $key, $default = null)
    {
        $meta = $this->meta;
        return $meta[$key] ?? $default;
    }

    public static function typeLabel(?string $type): string
    {
        return static::TYPES[$type] ?? 'Item';
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

    public static function usesYear(?string $type): bool
    {
        return in_array($type, static::YEAR_TYPES ?? [], true);
    }
}
