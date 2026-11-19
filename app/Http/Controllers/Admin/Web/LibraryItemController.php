<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\LibraryItem;

class LibraryItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Library Item';
        $this->route  = 'admin.library-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'library-items';
        $this->access = 'library-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return LibraryItem::class;
    }

    protected function titleOnFrontend(string $type): bool
    {
        return true;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'record' => [
                ['name' => 'noOfBooks', 'label' => 'No. of Books', 'type' => 'text', 'required' => true],
            ],
            default => [],
        };
    }
}
