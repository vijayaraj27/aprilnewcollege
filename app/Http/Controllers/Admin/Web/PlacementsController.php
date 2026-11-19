<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Placements;
use App\Models\Web\PlacementItem;
use Toastr;
use Auth;
class PlacementsController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Placements', 1);
        $this->route    = 'admin.placements';
        $this->view     = 'admin.web.placements';
        $this->path     = 'placements';
        $this->access   = 'placements';
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
        $data['placements'] = Placements::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(PlacementItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.placement-item.index', [
                'department_id' => $departmentId,
                'type' => PlacementItem::SECTION_TYPE_MAP[$section],
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

        $query = Placements::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Placements = Placements::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Placements) {
            $message = 'Record updated successfully';
        } else {
            $Placements = new Placements;
            $Placements->departmentId = $request->departmentId;
            $Placements->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Placements->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Placements->title = $request->title;
            $Placements->description = $request->description;
        } else {
            Toastr::error(__('These placements are managed from the Placement Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Placements->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
