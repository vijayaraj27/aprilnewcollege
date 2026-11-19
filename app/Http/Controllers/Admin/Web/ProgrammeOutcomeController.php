<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Language;
use App\Models\Web\ProgrammeOutcome;
use Auth;
use Illuminate\Http\Request;
use Toastr;

class ProgrammeOutcomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->title  = 'Programme Outcome';
        $this->route  = 'admin.programme-outcome';
        $this->view   = 'admin.web.programme-outcome';
        $this->path   = 'programme-outcome';
        $this->access = 'programme-outcome';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = ProgrammeOutcome::typeLabel($type);
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

        $query = ProgrammeOutcome::where('language_id', Language::version()->id)
            ->where('type', $type);

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

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $type = $this->resolveType($request);

        $data['title']  = ProgrammeOutcome::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:peo,po,pso',
            'description' => 'required|string',
            'title' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $outcome = new ProgrammeOutcome;
        $outcome->language_id = Language::version()->id;
        $outcome->type = $request->type;
        $outcome->title = ProgrammeOutcome::stripTypePrefix($request->title);
        $outcome->description = ProgrammeOutcome::stripTypePrefix($request->description);
        $outcome->sort_order = (int) ($request->sort_order ?? 0);
        $outcome->status = 1;

        if (Auth::user()->is_admin == '1') {
            $outcome->department_id = $request->department_id ?: $request->get('department_id');
        } else {
            $outcome->department_id = Auth::user()->department_id;
        }

        $outcome->save();

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $outcome));
    }

    /**
     * Display the specified resource.
     */
    public function show(ProgrammeOutcome $programme_outcome)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ProgrammeOutcome $programme_outcome)
    {
        $type = $programme_outcome->type ?: $this->resolveType($request);

        $data['title']  = ProgrammeOutcome::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['row']    = $programme_outcome;
        $data['type']   = $type;
        $data['filter'] = array_filter([
            'department_id' => $programme_outcome->department_id ?: $request->get('department_id'),
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
        }

        return view($this->view.'.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProgrammeOutcome $programme_outcome)
    {
        $request->validate([
            'type' => 'required|in:peo,po,pso',
            'description' => 'required|string',
            'title' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:0,1',
            'department_id' => 'nullable|integer',
        ]);

        $programme_outcome->department_id = Auth::user()->is_admin == '1'
            ? ($request->department_id ?: $programme_outcome->department_id)
            : Auth::user()->department_id;
        $programme_outcome->type = $request->type;
        $programme_outcome->title = ProgrammeOutcome::stripTypePrefix($request->title);
        $programme_outcome->description = ProgrammeOutcome::stripTypePrefix($request->description);
        $programme_outcome->sort_order = (int) ($request->sort_order ?? 0);
        $programme_outcome->status = $request->status !== null ? (int) $request->status : $programme_outcome->status;
        $programme_outcome->update();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $programme_outcome));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ProgrammeOutcome $programme_outcome)
    {
        $params = $this->redirectParams($request, $programme_outcome);
        $programme_outcome->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $params);
    }

    /**
     * Resolve and validate type from request.
     */
    private function resolveType(Request $request): string
    {
        $type = $request->get('type', 'peo');

        if (!array_key_exists($type, ProgrammeOutcome::TYPES)) {
            abort(404);
        }

        return $type;
    }

    /**
     * Build redirect query params preserving department + type.
     */
    private function redirectParams(Request $request, ?ProgrammeOutcome $outcome = null): array
    {
        $type = $request->get('type')
            ?: ($outcome->type ?? null)
            ?: 'peo';

        $departmentId = null;
        if (Auth::user()->is_admin == '1') {
            $departmentId = $request->get('department_id')
                ?: ($outcome->department_id ?? null);
        } else {
            $departmentId = Auth::user()->department_id;
        }

        return array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);
    }
}
