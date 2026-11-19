<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Library;
use App\Models\Web\LibraryItem;
use Auth;
use Toastr;

class LibraryController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Library', 1);
        $this->route    = 'admin.library';
        $this->view     = 'admin.web.library';
        $this->path     = 'library';
        $this->access   = 'library';
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
        $data['library'] = Library::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(LibraryItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.library-item.index', [
                'department_id' => $departmentId,
                'type' => LibraryItem::SECTION_TYPE_MAP[$section],
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

        $query = Library::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Library = Library::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Library) {
            $message = 'Record updated successfully';
        } else {
            $Library = new Library;
            $Library->departmentId = $request->departmentId;
            $Library->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Library->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Library->title = $request->title;
            $Library->description = $request->description;
        } elseif ($request->section === 'sectionAbout') {
            if ($request->hasFile('imageFile2')) {
                $Library->imageFile2 = $this->uploadImage($request, 'imageFile2', $this->path, null, 800);
            }
            $Library->title2 = $request->title2;
            $Library->description2 = $request->description2;
        } else {
            Toastr::error(__('These library records are managed from the Library Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Library->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
