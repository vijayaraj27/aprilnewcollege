<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\ActivityItem;

class ActivityItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Activity Item';
        $this->route  = 'admin.activity-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'activity-items';
        $this->access = 'activity-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return ActivityItem::class;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'department-activity' => [
                ['name' => 'teacherName', 'label' => 'Teacher Name', 'type' => 'text', 'required' => true],
                ['name' => 'programmeTitle', 'label' => 'Programme Title', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'organizer', 'label' => 'Organizer', 'type' => 'text'],
                ['name' => 'duration', 'label' => 'Duration', 'type' => 'text'],
            ],
            'student-participation' => [
                ['name' => 'eventDate', 'label' => 'Event Date', 'type' => 'text'],
                ['name' => 'eventName', 'label' => 'Event Name', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'conductedBy', 'label' => 'Conducted By', 'type' => 'text'],
                ['name' => 'nameOfTheStudentsParticipated', 'label' => 'Students Participated', 'type' => 'textarea', 'rows' => 4],
            ],
            'inter-institute-prize' => [
                ['name' => 'eventDate', 'label' => 'Event Date', 'type' => 'text'],
                ['name' => 'eventName', 'label' => 'Event Name', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'conductedBy', 'label' => 'Conducted By', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'nameOfTheStudentsParticipated', 'label' => 'Students Participated', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'prizeWon', 'label' => 'Prize Won', 'type' => 'text'],
            ],
            'industrial-visit' => [
                ['name' => 'nameOftheIndustry', 'label' => 'Name of the Industry', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'semester', 'label' => 'Semester', 'type' => 'text'],
                ['name' => 'staffAccompanied', 'label' => 'Staff Accompanied', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'Duration', 'label' => 'Duration', 'type' => 'text'],
            ],
            default => [],
        };
    }
}
