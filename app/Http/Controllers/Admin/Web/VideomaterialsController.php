<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Videomaterials;
use App\Models\Web\VideoItem;
use Toastr;
use Auth;
class VideomaterialsController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Videomaterials', 1);
        $this->route    = 'admin.videomaterials';
        $this->view     = 'admin.web.videomaterials';
        $this->path     = 'videomaterials';
        $this->access   = 'videomaterials';
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
        $data['videomaterials'] = Videomaterials::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(VideoItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.video-item.index', [
                'department_id' => $departmentId,
                'type' => VideoItem::SECTION_TYPE_MAP[$section],
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

        $query = Videomaterials::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Videomaterials = Videomaterials::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Videomaterials) {
            $message = 'Record updated successfully';
        } else {
            $Videomaterials = new Videomaterials;
            $Videomaterials->departmentId = $request->departmentId;
            $Videomaterials->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Videomaterials->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Videomaterials->title = $request->title;
            $Videomaterials->description = $request->description;
        } else {
            Toastr::error(__('These videos are managed from the Video Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Videomaterials->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
