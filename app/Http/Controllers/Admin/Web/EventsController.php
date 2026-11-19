<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Web\EventItem;
use App\Models\Web\Events;
use App\Traits\FileUploader;
use Auth;
use Illuminate\Http\Request;
use Toastr;

class EventsController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = trans_choice('Events', 1);
        $this->route  = 'admin.events';
        $this->view   = 'admin.web.events';
        $this->path   = 'events';
        $this->access = 'events';

        $this->middleware('permission:'.$this->access.'-view|event-item-view|event-view');
    }

    public function index()
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['baseurl'] = rtrim(config('app.url'), '/') . '/';
        $data['events'] = Events::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(EventItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.event-item.index', [
                'department_id' => $departmentId,
                'type' => EventItem::SECTION_TYPE_MAP[$section],
            ]);
        }

        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['baseurl'] = rtrim(config('app.url'), '/') . '/';
        $data['departmentId'] = $departmentId;
        $data['section'] = $section;
        $data['row'] = Events::where('departmentId', $departmentId)->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $events = Events::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($events) {
            $message = 'Record updated successfully';
        } else {
            $events = new Events;
            $events->departmentId = $request->departmentId;
            $events->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $events->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $events->title = $request->title;
            $events->description = $request->description;
        } else {
            Toastr::error(__('Event entries are managed from the Event Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $events->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
