<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\PlacementItem;

class PlacementItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Placement Item';
        $this->route  = 'admin.placement-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'placement-items';
        $this->access = 'placement-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return PlacementItem::class;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'student-placed' => [
                ['name' => 'studentRegNumber', 'label' => 'Registration Number', 'type' => 'text'],
                ['name' => 'studentName', 'label' => 'Student Name', 'type' => 'text', 'required' => true],
                ['name' => 'companyName', 'label' => 'Company Name', 'type' => 'text'],
            ],
            default => [],
        };
    }
}
