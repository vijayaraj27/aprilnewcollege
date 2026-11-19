<?php

namespace App\Http\Controllers\Admin\Web\Concerns;

use App\Models\Department;
use App\Models\Language;
use Auth;
use File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Toastr;

trait ManagesDepartmentContentItems
{
    abstract protected function modelClass(): string;

    abstract protected function formFields(string $type): array;

    /**
     * Whether the Title column is shown on the public department page for this type.
     * When false, the admin form keeps Title optional and labels it as admin-only.
     */
    protected function titleOnFrontend(string $type): bool
    {
        return false;
    }

    protected function showDescriptionField(string $type): bool
    {
        return false;
    }

    protected function descriptionRequired(string $type): bool
    {
        return false;
    }

    protected function hasImageFields(string $type): bool
    {
        foreach ($this->formFields($type) as $field) {
            if (($field['type'] ?? 'text') === 'image') {
                return true;
            }
        }

        return false;
    }

    protected function prepareFormData(array $data, string $type): array
    {
        $data['show_description'] = $this->showDescriptionField($type);
        $data['description_required'] = $this->descriptionRequired($type);
        $data['has_image_fields'] = $this->hasImageFields($type);

        return $data;
    }

    public function index(Request $request)
    {
        $model = $this->modelClass();
        $type = $this->resolveType($request);

        $data['title']  = $model::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['path']   = $this->path;
        $data['access'] = $this->access;
        $data['type']   = $type;
        $data['fields'] = $this->formFields($type);
        $data['uses_year'] = $model::usesYear($type);
        $data['title_on_frontend'] = $this->titleOnFrontend($type);
        $data = $this->prepareFormData($data, $type);

        $departmentId = $request->get('department_id');
        $data['filter'] = array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);

        $query = $model::where('type', $type);
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
        if ($model::usesYear($type)) {
            $rows = $rows->sortByDesc(fn ($row) => $model::yearSortKey($row->year))->values();
        }
        $data['rows'] = $this->sortIndexRows($rows, $type);

        return view($this->view.'.index', $data);
    }

    /**
     * Hook for type-specific listing order. Default keeps query order.
     */
    protected function sortIndexRows($rows, string $type)
    {
        return $rows;
    }

    public function create(Request $request)
    {
        $model = $this->modelClass();
        $type = $this->resolveType($request);

        $data['title']  = $model::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['type']   = $type;
        $data['fields'] = $this->formFields($type);
        $data['uses_year'] = $model::usesYear($type);
        $data['title_on_frontend'] = $this->titleOnFrontend($type);
        $data = $this->prepareFormData($data, $type);

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
        $model = $this->modelClass();
        $type = $this->resolveType($request);
        $request->merge(['type' => $type]);
        $this->validateItem($request, $type);

        /** @var Model $item */
        $item = new $model;
        $item->language_id = Language::version()->id;
        $item->type = $type;
        $item->status = 1;
        $this->fillItem($item, $request, $type);

        if (Auth::user()->is_admin == '1') {
            $item->department_id = $request->department_id ?: $request->get('department_id');
        } else {
            $item->department_id = Auth::user()->department_id;
        }

        $item->save();
        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $item));
    }

    public function show($item)
    {
        //
    }

    public function edit(Request $request, $item)
    {
        $item = $this->resolveItem($item);
        $model = $this->modelClass();
        $type = $item->type ?: $this->resolveType($request);

        $data['title']  = $model::typeLabel($type);
        $data['route']  = $this->route;
        $data['view']   = $this->view;
        $data['access'] = $this->access;
        $data['path']   = $this->path;
        $data['row']    = $item;
        $data['type']   = $type;
        $data['fields'] = $this->formFields($type);
        $data['uses_year'] = $model::usesYear($type);
        $data['title_on_frontend'] = $this->titleOnFrontend($type);
        $data = $this->prepareFormData($data, $type);
        $data['filter'] = array_filter([
            'department_id' => $item->department_id ?: $request->get('department_id'),
            'type' => $type,
        ]);

        if (Auth::user()->is_admin == '1') {
            $data['departments'] = Department::where('status', 1)->orderBy('title')->get();
        }

        return view($this->view.'.edit', $data);
    }

    public function update(Request $request, $item)
    {
        $item = $this->resolveItem($item);
        $type = $item->type ?: $this->resolveType($request);
        $request->merge(['type' => $type]);
        $this->validateItem($request, $type, true);

        $item->department_id = Auth::user()->is_admin == '1'
            ? ($request->department_id ?: $item->department_id)
            : Auth::user()->department_id;
        $item->type = $type;
        $item->status = $request->status !== null ? (int) $request->status : $item->status;
        $this->fillItem($item, $request, $type);
        $item->update();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route.'.index', $this->redirectParams($request, $item));
    }

    public function destroy(Request $request, $item)
    {
        $item = $this->resolveItem($item);
        $params = $this->redirectParams($request, $item);
        $item->delete();
        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));
        return redirect()->route($this->route.'.index', $params);
    }

    protected function resolveItem($item): Model
    {
        if ($item instanceof Model) {
            return $item;
        }
        $model = $this->modelClass();
        return $model::findOrFail($item);
    }

    protected function resolveType(Request $request): string
    {
        $model = $this->modelClass();
        $type = $request->get('type', array_key_first($model::TYPES));
        if (!array_key_exists($type, $model::TYPES)) {
            abort(404);
        }
        return $type;
    }

    protected function redirectParams(Request $request, ?Model $item = null): array
    {
        $model = $this->modelClass();
        $type = $request->get('type') ?: ($item->type ?? array_key_first($model::TYPES));
        $departmentId = Auth::user()->is_admin == '1'
            ? ($request->get('department_id') ?: ($item->department_id ?? null))
            : Auth::user()->department_id;

        return array_filter([
            'department_id' => $departmentId,
            'type' => $type,
        ]);
    }

    protected function validateItem(Request $request, string $type, bool $isUpdate = false): void
    {
        $model = $this->modelClass();
        $descriptionRule = $this->descriptionRequired($type) ? 'required|string' : 'nullable|string';
        $rules = [
            'type' => 'required|string',
            'sort_order' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'title' => 'nullable|string|max:1000',
            'description' => $descriptionRule,
            'year' => $model::usesYear($type) ? 'required|string|max:50' : 'nullable|string|max:50',
        ];
        if ($isUpdate) {
            $rules['status'] = 'nullable|in:0,1';
        }
        foreach ($this->formFields($type) as $field) {
            $key = $field['name'];
            $fieldType = $field['type'] ?? 'text';
            $req = !empty($field['required']) ? 'required' : 'nullable';

            if ($fieldType === 'image') {
                $rules['meta_file.'.$key] = $req.'|image|max:5120';
                continue;
            }

            $isLong = in_array($fieldType, ['textarea', 'editor'], true);
            $rules['meta.'.$key] = $req.'|string|max:'.($isLong ? '20000' : '2000');
        }
        $request->validate($rules);
    }

    protected function fillItem(Model $item, Request $request, string $type): void
    {
        $item->year = $request->year;
        // Respect an explicitly cleared Title (do not auto-copy from meta on update).
        $title = is_string($request->title) ? trim($request->title) : $request->title;
        $item->title = ($title === '' || $title === null) ? null : $title;
        $item->description = $request->description;
        $item->sort_order = (int) ($request->sort_order ?? 0);

        $meta = is_array($item->meta) ? $item->meta : [];
        foreach ($this->formFields($type) as $field) {
            $key = $field['name'];
            $fieldType = $field['type'] ?? 'text';

            if ($fieldType === 'image') {
                $uploaded = null;
                if (method_exists($this, 'uploadMedia')) {
                    $uploaded = $this->uploadMedia($request, 'meta_file.'.$key, $this->path);
                }
                if ($uploaded) {
                    $old = $meta[$key] ?? null;
                    if (is_string($old) && $old !== '' && !preg_match('#^https?://#i', $old)) {
                        $oldPath = public_path('uploads/'.$this->path.'/'.$old);
                        if (File::isFile($oldPath)) {
                            File::delete($oldPath);
                        }
                    }
                    $meta[$key] = $uploaded;
                } elseif (!array_key_exists($key, $meta)) {
                    $meta[$key] = null;
                }
                continue;
            }

            $meta[$key] = $request->input('meta.'.$key);
        }

        // Only auto-fill Title on create when the public site uses it and the field was left blank.
        if ($item->title === null && !$item->exists && $this->titleOnFrontend($type)) {
            foreach ($meta as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $item->title = mb_substr(trim($value), 0, 255);
                    break;
                }
            }
        }

        if ($item->title === null && !$item->exists && $this->showDescriptionField($type) && is_string($item->description)) {
            $snippet = trim(strip_tags($item->description));
            if ($snippet !== '') {
                $item->title = mb_substr($snippet, 0, 80);
            }
        }

        $item->meta = $meta;
    }
}
