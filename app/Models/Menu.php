<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';

    public $timestamps = false;

    protected $fillable = [
        'menu',
        'parent_id',
        'is_parent_present',
        'status',
        'location',
        'sort_order',
        'link_type',
        'link_value',
        'link_section',
        'open_in_new_tab',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'status' => 'integer',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeHeader($query)
    {
        return $query->where('location', 'header');
    }

    public function scopeFooter($query)
    {
        return $query->where('location', 'footer');
    }

    public function scopeActive($query)
    {
        // Historical convention in this project: status 0 = shown
        return $query->where('status', 0);
    }
}
