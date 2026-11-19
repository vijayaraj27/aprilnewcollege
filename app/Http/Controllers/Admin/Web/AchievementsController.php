<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Achievements;
use App\Models\Web\AchievementItem;
use Toastr;
use Auth;
class AchievementsController extends Controller
{
    use FileUploader;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Module Data
        $this->title    = trans_choice('Achievements', 1);
        $this->route    = 'admin.achievements';
        $this->view     = 'admin.web.achievements';
        $this->path     = 'achievements';
        $this->access   = 'achievements';
        $this->middleware('permission:'.$this->access.'-view');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function index()
        {
            $data['title']  = $this->title;
            $data['route']  = $this->route;
            $data['view']   = $this->view;
            $data['path']   = $this->path;
            $data['access'] = $this->access;
            $data['baseurl'] = rtrim(config('app.url'), '/') . '/';

            $data['achievements'] = Achievements::all();

            return view($this->view.'.index', $data);
        }


    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section= $result[1] ?? 'basic';

        if (isset(AchievementItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.achievement-item.index', [
                'department_id' => $departmentId,
                'type' => AchievementItem::SECTION_TYPE_MAP[$section],
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

        $query = Achievements::query();
        $query->where('departmentId', $departmentId);
        $data['row'] =  $query->first();

        return view($this->view.'.index', $data);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $departmentId = null)
    {
 
        // Check if the row exists with the given departmentId
        $Achievements = Achievements::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Achievements) {
            $message = 'Record updated successfully';
        } else {
            $Achievements = new Achievements;
            $Achievements->departmentId = $request->departmentId;
            $Achievements->designationId = 1;
            $message = 'Record created successfully';
        }

        if($request->section === 'basic'){
            if ($request->hasFile('imageFile')) {            
                $Achievements->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);                 
            }

            $Achievements->title = $request->title;
            $Achievements->description = $request->description;            
        } else {
            Toastr::error(__('These achievements are managed from the Achievement Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Achievements->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
