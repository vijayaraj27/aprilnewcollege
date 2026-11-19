<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Language;
use App\Models\Web\InfrastructureItem;
use App\Traits\FileUploader;
use Auth;
use File;
use Illuminate\Http\Request;
use Toastr;

class InfrastructureItemController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = 'Infrastructure Item';
        $this->route  = 'admin.infrastructure-item';
        $this->view   = 'admin.web.infrastructure-item';
        $this->path   = 'infrastructure-items';
        $this->access = 'infrastructure-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = InfrastructureItem::typeLabel($type);
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

        $query = InfrastructureItem::where('type', $type);

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

        $data['title']  = InfrastructureItem::typeLabel($type);
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
        $request->validate([
            'type' => 'required|in:buildings',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'sort_order' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $item = new InfrastructureItem;
        $item->language_id = Language::version()->id;
        $item->type = $request->type;
        $item->title = $request->title;
        $item->description = $request->description;
        $item->sort_order = (int) ($request->sort_order ?? 0);
        $item->status = 1;
        $item->image = $this->uploadImage($request, 'image', $this->path, 800, 600);

        if (Auth::user()->is_admin == '1') {
            $item->department_id = $request->department_id ?: $request->get('department_id');
        } else {
            $item->department_id = Auth::user()->department_id;
        }

        $item->save();
        $this->mirrorUploadToRoot($item->image);

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $item));
    }

    public function show(InfrastructureItem $infrastructure_item)
    {
        //
    }

    public function edit(Request $request, InfrastructureItem $infrastructure_item)
    {
        $type = $infrastructure_item->type ?: $this->resolveType($request);

        $data['title']  = InfrastructureItem::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['row']    = $infrastructure_item;
        $data['type']   = $type;
        $data['filter'] = array_filter([
            'department_id' => $infrastructure_item->department_id ?: $request->get('department_id'),
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
        }

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, InfrastructureItem $infrastructure_item)
    {
        $request->validate([
            'type' => 'required|in:buildings',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:0,1',
            'department_id' => 'nullable|integer',
        ]);

        $infrastructure_item->department_id = Auth::user()->is_admin == '1'
            ? ($request->department_id ?: $infrastructure_item->department_id)
            : Auth::user()->department_id;
        $infrastructure_item->type = $request->type;
        $infrastructure_item->title = $request->title;
        $infrastructure_item->description = $request->description;
        $infrastructure_item->sort_order = (int) ($request->sort_order ?? 0);
        $infrastructure_item->status = $request->status !== null ? (int) $request->status : $infrastructure_item->status;
        $infrastructure_item->image = $this->updateImage($request, 'image', $this->path, 800, 600, $infrastructure_item, 'image');
        $infrastructure_item->update();
        $this->mirrorUploadToRoot($infrastructure_item->image);

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $infrastructure_item));
    }

    public function destroy(Request $request, InfrastructureItem $infrastructure_item)
    {
        $params = $this->redirectParams($request, $infrastructure_item);
        if (!empty($infrastructure_item->image)) {
            foreach ([
                public_path('uploads/'.$this->path.'/'.$infrastructure_item->image),
                base_path('uploads/'.$this->path.'/'.$infrastructure_item->image),
            ] as $photoPath) {
                if (File::isFile($photoPath)) {
                    File::delete($photoPath);
                }
            }
        }
        $infrastructure_item->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $params);
    }

    private function resolveType(Request $request): string
    {
        $type = $request->get('type', 'buildings');

        if (!array_key_exists($type, InfrastructureItem::TYPES)) {
            abort(404);
        }

        return $type;
    }

    private function redirectParams(Request $request, ?InfrastructureItem $item = null): array
    {
        $type = $request->get('type') ?: ($item->type ?? 'buildings');

        $departmentId = null;
        if (Auth::user()->is_admin == '1') {
            $departmentId = $request->get('department_id') ?: ($item->department_id ?? null);
        } else {
            $departmentId = Auth::user()->department_id;
        }

        return array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);
    }

    private function mirrorUploadToRoot(?string $filename): void
    {
        if (empty($filename)) {
            return;
        }

        $from = public_path('uploads/'.$this->path.'/'.$filename);
        $toDir = base_path('uploads/'.$this->path.'/');
        if (!File::exists($toDir)) {
            File::makeDirectory($toDir, 0777, true, true);
        }
        $to = $toDir.$filename;
        if (File::isFile($from) && !File::isFile($to)) {
            File::copy($from, $to);
        }
    }
}
