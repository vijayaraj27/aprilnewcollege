<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Magazines;
use App\Models\Web\MagazineItem;
use Toastr;
use Auth;
class MagazinesController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Magazines', 1);
        $this->route    = 'admin.magazines';
        $this->view     = 'admin.web.magazines';
        $this->path     = 'magazines';
        $this->access   = 'magazines';
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
        $data['magazines'] = Magazines::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(MagazineItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.magazine-item.index', [
                'department_id' => $departmentId,
                'type' => MagazineItem::SECTION_TYPE_MAP[$section],
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

        $query = Magazines::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Magazines = Magazines::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Magazines) {
            $message = 'Record updated successfully';
        } else {
            $Magazines = new Magazines;
            $Magazines->departmentId = $request->departmentId;
            $Magazines->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Magazines->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Magazines->title = $request->title;
            $Magazines->description = $request->description;
        } else {
            Toastr::error(__('These magazines are managed from the Magazine Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Magazines->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
