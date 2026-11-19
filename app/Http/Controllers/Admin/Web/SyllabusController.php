<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Syllabus;
use App\Models\Web\SyllabusItem;
use Toastr;
use Auth;
class SyllabusController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Syllabus', 1);
        $this->route    = 'admin.syllabus';
        $this->view     = 'admin.web.syllabus';
        $this->path     = 'syllabus';
        $this->access   = 'syllabus';
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
        $data['syllabus'] = Syllabus::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(SyllabusItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.syllabus-item.index', [
                'department_id' => $departmentId,
                'type' => SyllabusItem::SECTION_TYPE_MAP[$section],
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

        $query = Syllabus::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Syllabus = Syllabus::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Syllabus) {
            $message = 'Record updated successfully';
        } else {
            $Syllabus = new Syllabus;
            $Syllabus->departmentId = $request->departmentId;
            $Syllabus->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Syllabus->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Syllabus->title = $request->title;
            $Syllabus->description = $request->description;
        } else {
            Toastr::error(__('These syllabus items are managed from the Syllabus Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Syllabus->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
