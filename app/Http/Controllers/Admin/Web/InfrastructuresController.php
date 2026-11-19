<?php
namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Infrastructures;
use App\Models\Language;
use Toastr;
use Auth;

class InfrastructuresController extends Controller
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
        $this->title    = trans_choice('Infrastructures', 1);
        $this->route    = 'admin.infrastructures';
        $this->view     = 'admin.web.infrastructures';
        $this->path     = 'infrastructures';
        $this->access   = 'infrastructures';
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
        $data['infrastructures'] = Infrastructures::all(); // Example: Fetch all infrastructures.

        return view($this->view.'.index', $data);
    }

    /**
     * Show a single resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if ($section === 'buildings') {
            return redirect()->route('admin.infrastructure-item.index', [
                'department_id' => $departmentId,
                'type' => 'buildings',
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

        $query = Infrastructures::query();
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
        // Validation Rules
        $rules = [
            'departmentId' => 'required|integer',
            'section' => 'required|string',
            'imageFile' => 'nullable|image|max:2048', // For basic section
            'title' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    
        $validated = $request->validate($rules);
    
        // Check if the row exists with the given departmentId
        $Infrastructures = Infrastructures::firstOrNew(['departmentId' => $request->departmentId]);
        $message = $Infrastructures->exists ? 'Record updated successfully' : 'Record created successfully';
    
        // Handle 'basic' section only (buildings use infrastructure-item CRUD)
        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Infrastructures->imageFile = $this->uploadImage(
                    $request->file('imageFile'), 
                    'imageFile', 
                    $this->path, 
                    null, 
                    800
                );
            }
            $Infrastructures->title = $request->title;
            $Infrastructures->description = $request->description;
        } else {
            Toastr::error(__('Buildings are managed from the Buildings CRUD page.'), __('msg_error'));
            return redirect()->back();
        }

        // Save the record
        $Infrastructures->save();
    
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
    

    
}
