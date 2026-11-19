<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\VideoItem;

class VideoItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Video Item';
        $this->route  = 'admin.video-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'video-items';
        $this->access = 'video-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return VideoItem::class;
    }

    protected function titleOnFrontend(string $type): bool
    {
        return true;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'videos' => [
                ['name' => 'YoutubeLink', 'label' => 'YouTube Link', 'type' => 'text', 'required' => true, 'wide' => true],
            ],
            default => [],
        };
    }
}
