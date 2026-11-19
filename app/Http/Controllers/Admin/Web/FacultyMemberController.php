<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Language;
use App\Models\Web\FacultyMember;
use App\Traits\FileUploader;
use Auth;
use File;
use Illuminate\Http\Request;
use Toastr;

class FacultyMemberController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = 'Faculty Member';
        $this->route  = 'admin.faculty-member';
        $this->view   = 'admin.web.faculty-member';
        $this->path   = 'faculty-members';
        $this->access = 'faculty-member';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = FacultyMember::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['type']   = $type;

        $departmentId = $request->get('department_id');
        $data['filter'] = array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);

        $query = FacultyMember::where('type', $type);

        if (Auth::user()->is_admin == '1') {
            if ($departmentId) {
                $query->where('department_id', $departmentId);
                $data['selected_department'] = Department::find($departmentId);
            }
        } else {
            $query->where('department_id', Auth::user()->department_id);
            $data['filter']['department_id'] = Auth::user()->department_id;
        }

        $data['rows'] = $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        return view($this->view.'.index', $data);
    }

    public function create(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = FacultyMember::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['type']   = $type;

        $departmentId = $request->get('department_id');
        $data['filter'] = array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
            if ($departmentId) {
                $data['selected_department_id'] = $departmentId;
            }
        } else {
            $data['filter']['department_id'] = Auth::user()->department_id;
            $data['selected_department_id'] = Auth::user()->department_id;
        }

        return view($this->view.'.create', $data);
    }

    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|in:teaching,non-teaching',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'au_fin' => 'nullable|string|max:255',
            'aicte_id' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'photo' => 'nullable|image',
            'sort_order' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ];

        if ($request->type === 'teaching') {
            $rules['email'] = 'nullable|email|max:255';
        }

        $request->validate($rules);

        $member = new FacultyMember;
        $member->language_id = Language::version()->id;
        $member->type = $request->type;
        $member->name = $request->name;
        $member->designation = $request->designation;
        $member->qualification = $request->qualification;
        $member->email = $request->email;
        $member->au_fin = $request->au_fin;
        $member->aicte_id = $request->aicte_id;
        $member->profile_url = $request->profile_url;
        $member->sort_order = (int) ($request->sort_order ?? 0);
        $member->status = 1;
        // Passport-ish portrait box; FileUploader keeps aspect ratio (no stretch)
        $member->photo = $this->uploadImage($request, 'photo', $this->path, 360, 450);

        if (Auth::user()->is_admin == '1') {
            $member->department_id = $request->department_id ?: $request->get('department_id');
        } else {
            $member->department_id = Auth::user()->department_id;
        }

        $member->save();

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $member));
    }

    public function show(FacultyMember $faculty_member)
    {
        //
    }

    public function edit(Request $request, FacultyMember $faculty_member)
    {
        $type = $faculty_member->type ?: $this->resolveType($request);

        $data['title']  = FacultyMember::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['row']    = $faculty_member;
        $data['type']   = $type;
        $data['filter'] = array_filter([
            'department_id' => $faculty_member->department_id ?: $request->get('department_id'),
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
        }

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, FacultyMember $faculty_member)
    {
        $request->validate([
            'type' => 'required|in:teaching,non-teaching',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'au_fin' => 'nullable|string|max:255',
            'aicte_id' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'photo' => 'nullable|image',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:0,1',
            'department_id' => 'nullable|integer',
        ]);

        $faculty_member->department_id = Auth::user()->is_admin == '1'
            ? ($request->department_id ?: $faculty_member->department_id)
            : Auth::user()->department_id;
        $faculty_member->type = $request->type;
        $faculty_member->name = $request->name;
        $faculty_member->designation = $request->designation;
        $faculty_member->qualification = $request->qualification;
        $faculty_member->email = $request->email;
        $faculty_member->au_fin = $request->au_fin;
        $faculty_member->aicte_id = $request->aicte_id;
        $faculty_member->profile_url = $request->profile_url;
        $faculty_member->sort_order = (int) ($request->sort_order ?? 0);
        $faculty_member->status = $request->status !== null ? (int) $request->status : $faculty_member->status;
        // Passport-ish portrait box; FileUploader keeps aspect ratio (no stretch)
        $faculty_member->photo = $this->updateImage($request, 'photo', $this->path, 360, 450, $faculty_member, 'photo');
        $faculty_member->update();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $faculty_member));
    }

    public function destroy(Request $request, FacultyMember $faculty_member)
    {
        $params = $this->redirectParams($request, $faculty_member);
        if (!empty($faculty_member->photo)) {
            $photoPath = public_path('uploads/'.$this->path.'/'.$faculty_member->photo);
            if (File::isFile($photoPath)) {
                File::delete($photoPath);
            }
        }
        $faculty_member->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $params);
    }

    private function resolveType(Request $request): string
    {
        $type = $request->get('type', 'teaching');

        if (!array_key_exists($type, FacultyMember::TYPES)) {
            abort(404);
        }

        return $type;
    }

    private function redirectParams(Request $request, ?FacultyMember $member = null): array
    {
        $type = $request->get('type') ?: ($member->type ?? 'teaching');

        $departmentId = null;
        if (Auth::user()->is_admin == '1') {
            $departmentId = $request->get('department_id') ?: ($member->department_id ?? null);
        } else {
            $departmentId = Auth::user()->department_id;
        }

        return array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);
    }
}
