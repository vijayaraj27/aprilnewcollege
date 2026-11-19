<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\MagazineItem;

class MagazineItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Magazine Item';
        $this->route  = 'admin.magazine-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'magazine-items';
        $this->access = 'magazine-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return MagazineItem::class;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'magazines' => [
                ['name' => 'pdfLink', 'label' => 'PDF / Link', 'type' => 'text', 'required' => true],
            ],
            default => [],
        };
    }
}
