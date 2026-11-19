<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Coursematerials;
use App\Models\Web\CourseItem;
use Toastr;
use Auth;
class CoursematerialsController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Coursematerials', 1);
        $this->route    = 'admin.coursematerials';
        $this->view     = 'admin.web.coursematerials';
        $this->path     = 'coursematerials';
        $this->access   = 'coursematerials';
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
        $data['coursematerials'] = Coursematerials::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(CourseItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.course-item.index', [
                'department_id' => $departmentId,
                'type' => CourseItem::SECTION_TYPE_MAP[$section],
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

        $query = Coursematerials::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Coursematerials = Coursematerials::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Coursematerials) {
            $message = 'Record updated successfully';
        } else {
            $Coursematerials = new Coursematerials;
            $Coursematerials->departmentId = $request->departmentId;
            $Coursematerials->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Coursematerials->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Coursematerials->title = $request->title;
            $Coursematerials->description = $request->description;
        } else {
            Toastr::error(__('These courses are managed from the Course Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Coursematerials->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
