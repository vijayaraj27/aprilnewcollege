<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\EventItem;
use App\Traits\FileUploader;

class EventItemController extends Controller
{
    use FileUploader;
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Event Item';
        $this->route  = 'admin.event-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'event-items';
        $this->access = 'event-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return EventItem::class;
    }

    protected function showDescriptionField(string $type): bool
    {
        return true;
    }

    protected function descriptionRequired(string $type): bool
    {
        return true;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'events' => [
                [
                    'name' => 'image1',
                    'label' => 'Image 1',
                    'type' => 'image',
                    'required' => false,
                    'hint' => 'Optional. Landscape photo works best.',
                ],
                [
                    'name' => 'image2',
                    'label' => 'Image 2',
                    'type' => 'image',
                    'required' => false,
                    'hint' => 'Optional. Shown beside Image 1 on the public page.',
                ],
            ],
            default => [],
        };
    }
}
