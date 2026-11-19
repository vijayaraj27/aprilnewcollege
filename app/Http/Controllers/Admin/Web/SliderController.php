<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Slider;
use App\Models\Language;
use Toastr;
use Auth;
//namespace App\Http\Controllers\Admin\Auth;
class SliderController extends Controller
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
        $this->title   = trans_choice('module_slider', 1);
        $this->route   = 'admin.slider';
        $this->view    = 'admin.web.slider';
        $this->path    = 'slider';
        $this->access  = 'slider';
        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index','show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create','store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        
        // Check if department_id is passed in URL (from department dashboard)
        $departmentId = $request->get('department_id');
        
        // Check if user is super admin
        if(Auth::user()->is_admin == '1') {
            if($departmentId) {
                // Super admin viewing specific department's sliders
                $data['rows'] = Slider::where('language_id', Language::version()->id)
                                     ->where('department_id', $departmentId)
                                     ->orderby('id', 'asc')
                                     ->get();
                $data['selected_department'] = \App\Models\Department::find($departmentId);
            } else {
                // Super admin viewing all departments' sliders
                $data['rows'] = Slider::where('language_id', Language::version()->id)
                                     ->orderby('id', 'asc')
                                     ->get();
            }
        } else {
            // Regular users can only see their department's sliders
            $data['rows'] = Slider::where('language_id', Language::version()->id)
                                 ->where('department_id', Auth::user()->department_id)
                                 ->orderby('id', 'asc')
                                 ->get();
        }
        return view($this->view.'.index', $data);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        
        // Check if department_id is passed in URL (from department dashboard)
        $departmentId = $request->get('department_id');
        
        // Add departments for super admin
        if(Auth::user()->is_admin == '1') {
            $data['departments'] = \App\Models\Department::where('status', 1)->orderBy('title')->get();
            if($departmentId) {
                $data['selected_department_id'] = $departmentId;
            }
        }
        
        return view($this->view.'.create', $data);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Field Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'button_link' => 'nullable|url',
            'attach' => 'required|image',
        ]);
        //Data Insert
        $slider = new Slider;
        $slider->language_id = Language::version()->id;
        
        // Determine department_id based on user type and URL parameter
        if(Auth::user()->is_admin == '1') {
            // Super admin can choose department from form or URL parameter
            $slider->department_id = $request->department_id ?: $request->get('department_id');
        } else {
            // Regular users use their own department
            $slider->department_id = Auth::user()->department_id;
        }
        $slider->title = $request->title;
        $slider->sub_title = $request->sub_title;
        $slider->button_text = $request->button_text;
        $slider->button_link = $request->button_link;
        $slider->attach = $this->uploadImage($request, 'attach', $this->path, 1920, 850);
        $slider->save();
        Toastr::success(__('msg_created_successfully'), __('msg_success'));
        return redirect()->route($this->route.'.index');
    }
    /**
     * Display the specified resource.
     */
    public function show(Slider $slider)
    {
        //
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Slider $slider)
    {
        //
        $data['title']  = $this->title;
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['row'] =  $slider;
        
        // Add departments for super admin
        if(Auth::user()->is_admin == '1') {
            $data['departments'] = \App\Models\Department::where('status', 1)->orderBy('title')->get();
        }
        
        return view($this->view.'.edit', $data);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Slider $slider)
    {
        //Field Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'button_link' => 'nullable|url',
            'attach' => 'nullable|image',
        ]);
        //Data Update
        // Super admin can choose department, regular users use their own
        $slider->department_id = Auth::user()->is_admin == '1' ? $request->department_id : Auth::user()->department_id;
        $slider->title = $request->title;
        $slider->sub_title = $request->sub_title;
        $slider->button_text = $request->button_text;
        $slider->button_link = $request->button_link;
        $slider->attach = $this->updateImage($request, 'attach', $this->path, 1920, 850, $slider, 'attach');
        $slider->status = $request->status;
        $slider->update();
        Toastr::success(__('msg_updated_successfully'), __('msg_success'));
        return redirect()->back();
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Slider $slider)
    {
        //Delete Attach
        $this->deleteMedia($this->path, $slider);
        //Delete Data
        $slider->delete();
        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));
        return redirect()->back();
    }
}
