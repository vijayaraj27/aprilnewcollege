<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Web\NotificationBoard;
use App\Traits\FileUploader;
use Auth;
use Illuminate\Http\Request;
use Toastr;

class NotificationBoardController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = 'Notification';
        $this->route  = 'admin.notification-board';
        $this->view   = 'admin.web.notification-board';
        $this->path   = 'notification-board';
        $this->access = 'notification-board';

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

        $query = NotificationBoard::query();

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

        $data['rows'] = $query->orderBy('notification_date', 'desc')->orderBy('display_order', 'asc')->get();

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
            'notification_date' => 'required|date',
            'link' => 'nullable|string|max:2000',
            'department_id' => 'nullable',
        ]);

        if ($request->hasFile('attach')) {
            $file = $this->uploadFile($request, 'attach', $this->path, null, null, 5120);
        }

        $notification = new NotificationBoard;
        $notification->department_id = $this->storeDepartmentId($request);
        $notification->title = $request->title;
        $notification->description = $request->description;
        $notification->notification_date = $request->notification_date;
        $notification->link = $request->link;
        $notification->attach = $file ?? null;
        $notification->is_new = $request->is_new ?? 0;
        $notification->status = $request->status ?? 1;
        $notification->display_order = $request->display_order ?? 0;
        $notification->save();

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $notification->department_id));
    }

    public function show(NotificationBoard $notificationBoard)
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['row']    = $notificationBoard;

        return view($this->view.'.show', $data);
    }

    public function edit(Request $request, NotificationBoard $notificationBoard)
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['row']    = $notificationBoard;
        $data['filter'] = $this->redirectParams($request, $notificationBoard->department_id);
        $data['selected_department_id'] = $notificationBoard->department_id;

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, NotificationBoard $notificationBoard)
    {
        $request->validate([
            'title' => 'required|max:191',
            'notification_date' => 'required|date',
            'link' => 'nullable|string|max:2000',
            'status' => 'nullable|in:0,1',
        ]);

        if ($request->hasFile('attach')) {
            $file = $this->uploadFile($request, 'attach', $this->path, null, null, 5120);
            $this->removeFile('uploads/'.$this->path.'/', $notificationBoard->attach);
        }

        // Keep existing department; do not change via form dropdown.
        $notificationBoard->title = $request->title;
        $notificationBoard->description = $request->description;
        $notificationBoard->notification_date = $request->notification_date;
        $notificationBoard->link = $request->link;
        $notificationBoard->attach = $file ?? $notificationBoard->attach;
        $notificationBoard->is_new = $request->is_new ?? 0;
        $notificationBoard->status = $request->status ?? $notificationBoard->status;
        $notificationBoard->display_order = $request->display_order ?? 0;
        $notificationBoard->save();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $notificationBoard->department_id));
    }

    public function destroy(Request $request, NotificationBoard $notificationBoard)
    {
        $filter = $this->redirectParams($request, $notificationBoard->department_id);
        $this->removeFile('uploads/'.$this->path.'/', $notificationBoard->attach);
        $notificationBoard->delete();

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
