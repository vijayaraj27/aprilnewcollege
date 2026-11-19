<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Activities;
use App\Models\Web\ActivityItem;
use Toastr;
use Auth;

class ActivitiesController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Activities', 1);
        $this->route    = 'admin.activities';
        $this->view     = 'admin.web.activities';
        $this->path     = 'activities';
        $this->access   = 'activities';
        $this->middleware('permission:'.$this->access.'-view');
    }

    public function index()
    {
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['baseurl'] = rtrim(config('app.url'), '/') . '/';
        $data['activities'] = Activities::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(ActivityItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.activity-item.index', [
                'department_id' => $departmentId,
                'type' => ActivityItem::SECTION_TYPE_MAP[$section],
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

        $query = Activities::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Activities = Activities::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Activities) {
            $message = 'Record updated successfully';
        } else {
            $Activities = new Activities;
            $Activities->departmentId = $request->departmentId;
            $Activities->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Activities->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Activities->title = $request->title;
            $Activities->description = $request->description;
        } else {
            Toastr::error(__('These activities are managed from the Activity Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Activities->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
