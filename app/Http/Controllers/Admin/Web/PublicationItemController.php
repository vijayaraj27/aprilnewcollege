<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\PublicationItem;

class PublicationItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Publication Item';
        $this->route  = 'admin.publication-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'publication-items';
        $this->access = 'publication-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return PublicationItem::class;
    }

    protected function titleOnFrontend(string $type): bool
    {
        return false;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'patent' => [
                ['name' => 'NameOfTheInventors', 'label' => 'Name of the Inventors', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'titleOfThePatents', 'label' => 'Title of the Patents', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'patentNumber', 'label' => 'Patent Number', 'type' => 'text'],
                ['name' => 'publishedDate', 'label' => 'Published Date', 'type' => 'text'],
            ],
            'book-chapter' => [
                [
                    'name' => 'bookName',
                    'label' => 'Book Chapter / Citation',
                    'type' => 'textarea',
                    'rows' => 6,
                    'required' => true,
                    'hint' => 'Full citation text shown on the public publications page.',
                ],
            ],
            'journal' => [
                [
                    'name' => 'journalName',
                    'label' => 'Journal Publication / Citation',
                    'type' => 'textarea',
                    'rows' => 8,
                    'required' => true,
                    'hint' => 'Full citation text shown on the public publications page. Title above is admin-only and is not shown publicly.',
                ],
            ],
            'conference' => [
                [
                    'name' => 'list',
                    'label' => 'Conference Name / List',
                    'type' => 'textarea',
                    'rows' => 6,
                    'required' => true,
                    'hint' => 'Shown on the public publications page.',
                ],
            ],
            default => [],
        };
    }
}
