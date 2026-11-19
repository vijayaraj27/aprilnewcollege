<?php
namespace App\Http\Controllers\Admin\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Web\Newsletters;
use App\Models\Web\NewsletterItem;
use Toastr;
use Auth;
class NewsLettersController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title    = trans_choice('Newsletters', 1);
        $this->route    = 'admin.newsletters';
        $this->view     = 'admin.web.newsletters';
        $this->path     = 'newsletters';
        $this->access   = 'newsletters';
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
        $data['newsletters'] = Newsletters::all();

        return view($this->view.'.index', $data);
    }

    public function show($slug)
    {
        $result = explode('_', $slug);
        $departmentId = $result[0];
        $section = $result[1] ?? 'basic';

        if (isset(NewsletterItem::SECTION_TYPE_MAP[$section])) {
            return redirect()->route('admin.newsletter-item.index', [
                'department_id' => $departmentId,
                'type' => NewsletterItem::SECTION_TYPE_MAP[$section],
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

        $query = Newsletters::query();
        $query->where('departmentId', $departmentId);
        $data['row'] = $query->first();

        return view($this->view.'.index', $data);
    }

    public function store(Request $request, $departmentId = null)
    {
        $Newsletters = Newsletters::where('departmentId', $request->departmentId)->first();

        if (Auth::user()->department_id != 0 && $request->departmentId != Auth::user()->department_id) {
            Toastr::error(__("Sorry you can't edit some other information without their access"), __('msg_error'));
            return redirect()->back();
        }

        if ($Newsletters) {
            $message = 'Record updated successfully';
        } else {
            $Newsletters = new Newsletters;
            $Newsletters->departmentId = $request->departmentId;
            $Newsletters->designationId = 1;
            $message = 'Record created successfully';
        }

        if ($request->section === 'basic') {
            if ($request->hasFile('imageFile')) {
                $Newsletters->imageFile = $this->uploadImage($request, 'imageFile', $this->path, null, 800);
            }
            $Newsletters->title = $request->title;
            $Newsletters->description = $request->description;
        } else {
            Toastr::error(__('These newsletters are managed from the Newsletter Item CRUD pages.'), __('msg_error'));
            return redirect()->back();
        }

        $Newsletters->save();
        Toastr::success(__($message), __('msg_success'));
        return redirect()->back();
    }
}
