<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Publications;
use App\Models\Web\PublicationItem;
use Auth;
use Toastr;

class PublicationsController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Publications', 1);
        $this->route    = 'admin.publications';
        $this->view     = 'admin.web.publications';
        $this->path     = 'publications';
        $this->access   = 'publications';
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
        $data['publications'] = Publications::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(PublicationItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.publication-item.index', [
                'department_id' => $departmentId,
                'type' => PublicationItem::SECTION_TYPE_MAP[$section],
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

        $query = Publications::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Publications = Publications::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Publications) {
            $message = 'Record updated successfully';
        } else {
            $Publications = new Publications;
            $Publications->departmentId = $request->departmentId;
            $Publications->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Publications->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Publications->title = $request->title;
            $Publications->description = $request->description;
        } elseif ($request->section === 'sectionAbout') {
            if ($request->hasFile('imageFile2')) {
                $Publications->imageFile2 = $this->uploadImage($request, 'imageFile2', $this->path, null, 800);
            }
            $Publications->title2 = $request->title2;
            $Publications->description2 = $request->description2;
        } else {
            Toastr::error(__('These publications are managed from the Publication Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Publications->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
