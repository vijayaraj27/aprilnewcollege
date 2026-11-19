<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Language;
use App\Models\Web\AchievementItem;
use App\Traits\FileUploader;
use Auth;
use File;
use Illuminate\Http\Request;
use Toastr;

class AchievementItemController extends Controller
{
    use FileUploader;

    public function __construct()
    {
        $this->title  = 'Achievement Item';
        $this->route  = 'admin.achievement-item';
        $this->view   = 'admin.web.achievement-item';
        $this->path   = 'achievement-items';
        $this->access = 'achievement-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = AchievementItem::typeLabel($type);
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

        $query = AchievementItem::where('type', $type);

        if (Auth::user()->is_admin == '1') {
            if ($departmentId) {
                $query->where('department_id', $departmentId);
                $data['selected_department'] = Department::find($departmentId);
            }
        } else {
            $query->where('department_id', Auth::user()->department_id);
            $data['filter']['department_id'] = Auth::user()->department_id;
        }

        $rows = $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        if ($type !== 'staff') {
            $rows = $rows->sortByDesc(function ($row) {
                return AchievementItem::yearSortKey($row->year);
            })->values();
        }
        $data['rows'] = $rows;

        return view($this->view.'.index', $data);
    }

    public function create(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = AchievementItem::typeLabel($type);
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
        $type = $this->resolveType($request);
        $request->merge(['type' => $type]);
        $this->validateByType($request, $type);

        $item = new AchievementItem;
        $item->language_id = Language::version()->id;
        $item->type = $type;
        $item->status = 1;
        $this->fillByType($item, $request, $type, true);

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

    public function show(AchievementItem $achievement_item)
    {
        //
    }

    public function edit(Request $request, AchievementItem $achievement_item)
    {
        $type = $achievement_item->type ?: $this->resolveType($request);

        $data['title']  = AchievementItem::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['row']    = $achievement_item;
        $data['type']   = $type;
        $data['filter'] = array_filter([
            'department_id' => $achievement_item->department_id ?: $request->get('department_id'),
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
        }

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, AchievementItem $achievement_item)
    {
        $type = $achievement_item->type ?: $this->resolveType($request);
        $request->merge(['type' => $type]);
        $this->validateByType($request, $type, true);

        $achievement_item->department_id = Auth::user()->is_admin == '1'
            ? ($request->department_id ?: $achievement_item->department_id)
            : Auth::user()->department_id;
        $achievement_item->type = $type;
        $achievement_item->status = $request->status !== null ? (int) $request->status : $achievement_item->status;
        $this->fillByType($achievement_item, $request, $type, false);
        $achievement_item->update();
        $this->mirrorUploadToRoot($achievement_item->image);

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $achievement_item));
    }

    public function destroy(Request $request, AchievementItem $achievement_item)
    {
        $params = $this->redirectParams($request, $achievement_item);
        if (!empty($achievement_item->image)) {
            foreach ([
                public_path('uploads/'.$this->path.'/'.$achievement_item->image),
                base_path('uploads/'.$this->path.'/'.$achievement_item->image),
            ] as $photoPath) {
                if (File::isFile($photoPath)) {
                    File::delete($photoPath);
                }
            }
        }
        $achievement_item->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $params);
    }

    private function validateByType(Request $request, string $type, bool $isUpdate = false): void
    {
        $rules = [
            'type' => 'required|in:staff,student,student-table,student-appreciation',
            'sort_order' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ];

        if ($isUpdate) {
            $rules['status'] = 'nullable|in:0,1';
        }

        switch ($type) {
            case 'staff':
                $rules['description'] = 'required|string';
                break;
            case 'student':
                $rules['year'] = 'required|string|max:50';
                $rules['title'] = 'nullable|string|max:255';
                $rules['description'] = 'nullable|string';
                $rules['image'] = 'nullable|image';
                break;
            case 'student-table':
                $rules['year'] = 'required|string|max:50';
                $rules['student_name'] = 'required|string|max:255';
                $rules['event_nature'] = 'nullable|string|max:255';
                $rules['event_name'] = 'nullable|string|max:255';
                $rules['period_date'] = 'nullable|string|max:255';
                $rules['organized_by'] = 'nullable|string|max:255';
                $rules['awards'] = 'nullable|string|max:255';
                break;
            case 'student-appreciation':
                $rules['year'] = 'required|string|max:50';
                $rules['student_name'] = 'required|string|max:255';
                $rules['year_of_student'] = 'nullable|string|max:100';
                $rules['event_name'] = 'nullable|string|max:255';
                $rules['project_name'] = 'nullable|string|max:255';
                $rules['supervisor'] = 'nullable|string|max:255';
                $rules['result_status'] = 'nullable|string|max:255';
                break;
            default:
                abort(404);
        }

        $request->validate($rules);
    }

    private function fillByType(AchievementItem $item, Request $request, string $type, bool $isCreate): void
    {
        $item->sort_order = (int) ($request->sort_order ?? 0);
        $item->year = null;
        $item->title = null;
        $item->description = null;
        $item->student_name = null;
        $item->event_nature = null;
        $item->event_name = null;
        $item->period_date = null;
        $item->organized_by = null;
        $item->awards = null;
        $item->year_of_student = null;
        $item->project_name = null;
        $item->supervisor = null;
        $item->result_status = null;

        switch ($type) {
            case 'staff':
                $item->description = $request->description;
                break;
            case 'student':
                $item->year = $request->year;
                $item->title = $request->title;
                $item->description = $request->description;
                if ($isCreate) {
                    $item->image = $this->uploadImage($request, 'image', $this->path, 800, 600);
                } else {
                    $item->image = $this->updateImage($request, 'image', $this->path, 800, 600, $item, 'image');
                }
                break;
            case 'student-table':
                $item->year = $request->year;
                $item->student_name = $request->student_name;
                $item->event_nature = $request->event_nature;
                $item->event_name = $request->event_name;
                $item->period_date = $request->period_date;
                $item->organized_by = $request->organized_by;
                $item->awards = $request->awards;
                break;
            case 'student-appreciation':
                $item->year = $request->year;
                $item->student_name = $request->student_name;
                $item->year_of_student = $request->year_of_student;
                $item->event_name = $request->event_name;
                $item->project_name = $request->project_name;
                $item->supervisor = $request->supervisor;
                $item->result_status = $request->result_status;
                break;
            default:
                abort(404);
        }
    }

    private function resolveType(Request $request): string
    {
        $type = $request->get('type', 'staff');

        if (!array_key_exists($type, AchievementItem::TYPES)) {
            abort(404);
        }

        return $type;
    }

    private function redirectParams(Request $request, ?AchievementItem $item = null): array
    {
        $type = $request->get('type') ?: ($item->type ?? 'staff');

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
