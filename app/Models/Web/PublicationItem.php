<?php

namespace App\Models\Web;

use App\Models\Language;
use App\Models\Web\Concerns\IsDepartmentContentItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationItem extends Model
{
    use HasFactory;
    use IsDepartmentContentItem;

    protected $table = 'department_publication_items';

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
        'patent' => 'Patents',
        'book-chapter' => 'Book Chapters',
        'journal' => 'Journal Publications',
        'conference' => 'Conference List',
    ];

    public const YEAR_TYPES = [
        'book-chapter',
        'journal',
    ];

    public const SECTION_TYPE_MAP = [
        'patent' => 'patent',
        'bookchapter' => 'book-chapter',
        'journalpublication' => 'journal',
        'conferenceList' => 'conference',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
