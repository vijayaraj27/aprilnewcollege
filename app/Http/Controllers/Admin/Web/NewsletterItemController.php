<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\NewsletterItem;

class NewsletterItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Newsletter Item';
        $this->route  = 'admin.newsletter-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'newsletter-items';
        $this->access = 'newsletter-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return NewsletterItem::class;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'newsletters' => [
                ['name' => 'pdfLink', 'label' => 'PDF / Link', 'type' => 'text', 'required' => true],
            ],
            default => [],
        };
    }
}
