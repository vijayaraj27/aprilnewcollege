<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Research;
use App\Models\Web\ResearchItem;
use Auth;
use Toastr;

class ResearchController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Research', 1);
        $this->route    = 'admin.research';
        $this->view     = 'admin.web.research';
        $this->path     = 'research';
        $this->access   = 'research';
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
        $data['research'] = Research::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(ResearchItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.research-item.index', [
                'department_id' => $departmentId,
                'type' => ResearchItem::SECTION_TYPE_MAP[$section],
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

        $query = Research::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Research = Research::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Research) {
            $message = 'Record updated successfully';
        } else {
            $Research = new Research;
            $Research->departmentId = $request->departmentId;
            $Research->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Research->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Research->title = $request->title;
            $Research->description = $request->description;
        } elseif ($request->section === 'sectionAbout') {
            if ($request->hasFile('imageFile2')) {
                $Research->imageFile2 = $this->uploadImage($request, 'imageFile2', $this->path, null, 800);
            }
            $Research->title2 = $request->title2;
            $Research->description2 = $request->description2;
        } else {
            Toastr::error(__('These research items are managed from the Research Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Research->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
