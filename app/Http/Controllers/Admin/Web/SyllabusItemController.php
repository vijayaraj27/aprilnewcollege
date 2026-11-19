<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\SyllabusItem;

class SyllabusItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Syllabus Item';
        $this->route  = 'admin.syllabus-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'syllabus-items';
        $this->access = 'syllabus-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return SyllabusItem::class;
    }

    protected function titleOnFrontend(string $type): bool
    {
        return true;
    }

    protected function sortIndexRows($rows, string $type)
    {
        return SyllabusItem::sortForListing($rows);
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'syllabus', 'question-bank' => [
                ['name' => 'programType', 'label' => 'Program Type', 'type' => 'text', 'required' => true],
                ['name' => 'pdfLink', 'label' => 'PDF / Link', 'type' => 'text', 'required' => true, 'wide' => true],
            ],
            'regulation' => [
                ['name' => 'programType', 'label' => 'Program Type', 'type' => 'text', 'required' => true],
                ['name' => 'regulationType', 'label' => 'Regulation Type', 'type' => 'text', 'required' => true],
                ['name' => 'pdfLink', 'label' => 'PDF / Link', 'type' => 'text', 'required' => true, 'wide' => true],
            ],
            default => [],
        };
    }
}
