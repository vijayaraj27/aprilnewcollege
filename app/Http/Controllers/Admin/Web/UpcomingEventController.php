<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Web\UpcomingEvent;
use App\Traits\FileUploader;
use Auth;
use Illuminate\Http\Request;
use Toastr;

class UpcomingEventController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = 'Upcoming Event';
        $this->route  = 'admin.upcoming-event';
        $this->view   = 'admin.web.upcoming-event';
        $this->path   = 'upcoming-event';
        $this->access = 'upcoming-event';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;

        $departmentId = $this->resolveScopedDepartmentId($request);
        $data['filter'] = array_filter([
            'department_id' => $departmentId,
        ], fn ($value) => $value !== null && $value !== '');

        $query = UpcomingEvent::query();

        if (Auth::user()->is_admin == '1') {
            if ($departmentId === 'home') {
                $query->whereNull('department_id');
                $data['selected_department_label'] = 'Home Page';
            } elseif ($departmentId) {
                $query->where('department_id', $departmentId);
                $data['selected_department'] = Department::find($departmentId);
            }
        } else {
            $query->where('department_id', Auth::user()->department_id);
            $data['filter']['department_id'] = Auth::user()->department_id;
            $data['selected_department'] = Department::find(Auth::user()->department_id);
        }

        $data['rows'] = $query->orderBy('event_date', 'desc')->orderBy('display_order', 'asc')->get();

        return view($this->view.'.index', $data);
    }

    public function create(Request $request)
    {
        $departmentId = $this->resolveScopedDepartmentId($request);

        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['filter'] = array_filter([
            'department_id' => $departmentId,
        ], fn ($value) => $value !== null && $value !== '');
        $data['selected_department_id'] = $departmentId === 'home' ? null : $departmentId;

        return view($this->view.'.create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:191',
            'event_date' => 'required|date',
            'link' => 'nullable|string|max:2000',
            'department_id' => 'nullable',
        ]);

        if ($request->hasFile('attach')) {
            $file = $this->uploadFile($request, 'attach', $this->path, 800, 600);
        }

        $event = new UpcomingEvent;
        $event->department_id = $this->storeDepartmentId($request);
        $event->title = $request->title;
        $event->description = $request->description;
        $event->event_date = $request->event_date;
        $event->event_time = $request->event_time;
        $event->venue = $request->venue;
        $event->link = $request->link;
        $event->attach = $file ?? null;
        $event->status = $request->status ?? 1;
        $event->display_order = $request->display_order ?? 0;
        $event->save();

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $event->department_id));
    }

    public function show(UpcomingEvent $upcomingEvent)
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['row']    = $upcomingEvent;

        return view($this->view.'.show', $data);
    }

    public function edit(Request $request, UpcomingEvent $upcomingEvent)
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['row']    = $upcomingEvent;
        $data['filter'] = $this->redirectParams($request, $upcomingEvent->department_id);
        $data['selected_department_id'] = $upcomingEvent->department_id;

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, UpcomingEvent $upcomingEvent)
    {
        $request->validate([
            'title' => 'required|max:191',
            'event_date' => 'required|date',
            'link' => 'nullable|string|max:2000',
            'status' => 'nullable|in:0,1',
        ]);

        if ($request->hasFile('attach')) {
            $file = $this->uploadFile($request, 'attach', $this->path, 800, 600);
            $this->removeFile('uploads/'.$this->path.'/', $upcomingEvent->attach);
        }

        // Keep existing department; do not change via form dropdown.
        $upcomingEvent->title = $request->title;
        $upcomingEvent->description = $request->description;
        $upcomingEvent->event_date = $request->event_date;
        $upcomingEvent->event_time = $request->event_time;
        $upcomingEvent->venue = $request->venue;
        $upcomingEvent->link = $request->link;
        $upcomingEvent->attach = $file ?? $upcomingEvent->attach;
        $upcomingEvent->status = $request->status ?? $upcomingEvent->status;
        $upcomingEvent->display_order = $request->display_order ?? 0;
        $upcomingEvent->save();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $upcomingEvent->department_id));
    }

    public function destroy(Request $request, UpcomingEvent $upcomingEvent)
    {
        $filter = $this->redirectParams($request, $upcomingEvent->department_id);
        $this->removeFile('uploads/'.$this->path.'/', $upcomingEvent->attach);
        $upcomingEvent->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $filter);
    }

    protected function resolveScopedDepartmentId(Request $request)
    {
        if (Auth::user()->is_admin != '1') {
            return Auth::user()->department_id;
        }

        return $request->get('department_id');
    }

    protected function storeDepartmentId(Request $request)
    {
        if (Auth::user()->is_admin != '1') {
            return Auth::user()->department_id;
        }

        $departmentId = $request->input('department_id', $request->get('department_id'));
        if ($departmentId === null || $departmentId === '' || $departmentId === 'home') {
            return null;
        }

        return (int) $departmentId;
    }

    protected function redirectParams(Request $request, $departmentId = null): array
    {
        if (Auth::user()->is_admin != '1') {
            return array_filter(['department_id' => Auth::user()->department_id]);
        }

        $value = $request->get('department_id', $departmentId);
        if ($value === null || $value === '') {
            return [];
        }

        return ['department_id' => $value];
    }
}
