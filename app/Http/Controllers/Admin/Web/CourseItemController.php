<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\CourseItem;

class CourseItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Course Item';
        $this->route  = 'admin.course-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'course-items';
        $this->access = 'course-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return CourseItem::class;
    }

    protected function titleOnFrontend(string $type): bool
    {
        return true;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'courses' => [
                ['name' => 'pdfLink', 'label' => 'PDF / Link', 'type' => 'text', 'required' => true, 'wide' => true],
            ],
            default => [],
        };
    }
}
