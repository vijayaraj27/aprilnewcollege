<?php

namespace App\Http\Controllers\Admin\Web\Concerns;

use App\Models\Department;
use App\Models\Language;
use App\Models\Menu;
use App\Models\Web\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Toastr;

trait ManagesSiteMenus
{
    /**
     * Department public inner sections.
     */
    protected function departmentSections(): array
    {
        return [
            'about' => 'About Us',
            'achievements' => 'Achievements',
            'activities' => 'Activities',
            'courses' => 'Courses',
            'events' => 'Events',
            'faculties' => 'Faculties',
            'infrastructures' => 'Infrastructures',
            'libraries' => 'Libraries',
            'magazines' => 'Magazines',
            'newsletters' => 'Newsletters',
            'placements' => 'Placements',
            'publications' => 'Publications',
            'research' => 'Research',
            'syllabus' => 'Syllabus',
            'videos' => 'Videos',
        ];
    }

    protected function linkTypes(): array
    {
        return [
            'none' => 'None (parent / no link)',
            'page' => 'General Page',
            'department' => 'Department / Department Section',
            'custom' => 'Custom URL / PDF',
            'legacy' => 'Legacy (auto by label)',
        ];
    }

    protected function formLookups(?int $excludeId = null): array
    {
        $parentsQuery = Menu::query()
            ->where('location', $this->location)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($excludeId) {
            $excludeIds = $this->descendantIds($excludeId);
            $excludeIds[] = $excludeId;
            $parentsQuery->whereNotIn('id', $excludeIds);
        }

        $parents = $parentsQuery->get();

        return [
            'parents' => $this->flattenForSelect($parents),
            'pages' => Page::query()
                ->when(class_exists(Language::class) && method_exists(Language::class, 'version'), function ($q) {
                    try {
                        $q->where('language_id', Language::version()->id);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                })
                ->where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),
            'departments' => Department::query()
                ->where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),
            'sections' => $this->departmentSections(),
            'linkTypes' => $this->linkTypes(),
        ];
    }

    protected function flattenForSelect(Collection $menus, int $parentId = 0, int $depth = 0): Collection
    {
        $out = collect();
        foreach ($menus->where('parent_id', $parentId)->sortBy([
            ['sort_order', 'asc'],
            ['id', 'asc'],
        ]) as $menu) {
            $menu->depth = $depth;
            $menu->label_indented = str_repeat('— ', $depth) . $menu->menu;
            $out->push($menu);
            $out = $out->merge($this->flattenForSelect($menus, (int) $menu->id, $depth + 1));
        }

        return $out;
    }

    protected function treeRows(): Collection
    {
        $menus = Menu::query()
            ->where('location', $this->location)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->flattenForSelect($menus);
    }

    protected function descendantIds(int $id): array
    {
        $ids = [];
        $children = Menu::where('parent_id', $id)->pluck('id');
        foreach ($children as $childId) {
            $ids[] = (int) $childId;
            $ids = array_merge($ids, $this->descendantIds((int) $childId));
        }

        return $ids;
    }

    protected function validatedMenu(Request $request, ?Menu $menu = null): array
    {
        $linkType = $request->input('link_type', 'none');

        // Prefer typed fields from the form when present (works even if JS did not sync).
        if ($linkType === 'page' && $request->filled('page_slug')) {
            $request->merge(['link_value' => $request->input('page_slug')]);
        } elseif ($linkType === 'department' && $request->filled('department_slug')) {
            $request->merge(['link_value' => $request->input('department_slug')]);
        } elseif ($linkType === 'custom' && $request->filled('custom_url')) {
            $request->merge(['link_value' => $request->input('custom_url')]);
        }

        $rules = [
            'menu' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    $value = (int) $value;
                    if ($value === 0) {
                        return;
                    }
                    $exists = Menu::where('id', $value)->where('location', $this->location)->exists();
                    if (!$exists) {
                        $fail('The selected parent menu is invalid.');
                    }
                },
            ],
            'sort_order' => 'nullable|integer|min:0',
            'link_type' => 'required|in:none,page,department,custom,legacy',
            'link_value' => 'nullable|string|max:500',
            'link_section' => 'nullable|string|max:100',
            'open_in_new_tab' => 'nullable|boolean',
            // Active for menus historically = 0, inactive = 1
            'status' => 'required|in:0,1',
        ];

        if ($linkType === 'page') {
            $rules['link_value'] = 'required|string|max:255';
        } elseif ($linkType === 'department') {
            $rules['link_value'] = 'required|string|max:255';
            $rules['link_section'] = ['nullable', 'string', Rule::in(array_keys($this->departmentSections()))];
        } elseif ($linkType === 'custom') {
            $rules['link_value'] = 'required|string|max:500';
        }

        $data = $request->validate($rules);

        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($menu && $parentId === (int) $menu->id) {
            $parentId = 0;
        }
        if ($menu && in_array($parentId, $this->descendantIds((int) $menu->id), true)) {
            $parentId = 0;
        }

        $linkValue = $data['link_value'] ?? null;
        $linkSection = $data['link_section'] ?? null;

        if (($data['link_type'] ?? '') === 'none') {
            $linkValue = null;
            $linkSection = null;
        } elseif (($data['link_type'] ?? '') === 'page') {
            $linkSection = null;
        } elseif (($data['link_type'] ?? '') === 'custom') {
            $linkSection = null;
        } elseif (($data['link_type'] ?? '') === 'legacy') {
            $linkValue = null;
            $linkSection = null;
        } elseif (($data['link_type'] ?? '') === 'department' && empty($linkSection)) {
            $linkSection = null;
        }

        return [
            'menu' => $data['menu'],
            'parent_id' => $parentId,
            'is_parent_present' => $parentId > 0 ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'link_type' => $data['link_type'],
            'link_value' => $linkValue,
            'link_section' => $linkSection,
            'open_in_new_tab' => !empty($data['open_in_new_tab']) ? 1 : 0,
            'status' => (int) $data['status'],
            'location' => $this->location,
        ];
    }

    public function index()
    {
        $data = $this->baseViewData();
        $data['rows'] = $this->treeRows();

        return view($this->view . '.index', $data);
    }

    public function create()
    {
        $data = array_merge($this->baseViewData(), $this->formLookups());
        $data['row'] = null;

        return view($this->view . '.create', $data);
    }

    public function store(Request $request)
    {
        $payload = $this->validatedMenu($request);
        Menu::create($payload);

        Toastr::success(__('msg_created_successfully'), __('msg_success'));

        return redirect()->route($this->route . '.index');
    }

    public function edit($id)
    {
        $row = Menu::where('location', $this->location)->findOrFail($id);
        $data = array_merge($this->baseViewData(), $this->formLookups((int) $row->id));
        $data['row'] = $row;

        return view($this->view . '.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $row = Menu::where('location', $this->location)->findOrFail($id);
        $payload = $this->validatedMenu($request, $row);
        $row->fill($payload);
        $row->save();

        Toastr::success(__('msg_updated_successfully'), __('msg_success'));

        return redirect()->route($this->route . '.index');
    }

    public function destroy($id)
    {
        $row = Menu::where('location', $this->location)->findOrFail($id);

        // Re-parent children to this item's parent
        Menu::where('parent_id', $row->id)->update([
            'parent_id' => $row->parent_id,
            'is_parent_present' => ((int) $row->parent_id > 0) ? 1 : 0,
        ]);

        $row->delete();

        Toastr::success(__('msg_deleted_successfully'), __('msg_success'));

        return redirect()->route($this->route . '.index');
    }

    protected function baseViewData(): array
    {
        return [
            'title' => $this->title,
            'route' => $this->route,
            'view' => $this->view,
            'access' => $this->access,
            'location' => $this->location,
            'sections' => $this->departmentSections(),
            'linkTypes' => $this->linkTypes(),
        ];
    }

    protected function describeLink(Menu $row): string
    {
        switch ($row->link_type) {
            case 'none':
                return 'None';
            case 'page':
                return 'Page: ' . ($row->link_value ?: '—');
            case 'department':
                $label = 'Dept: ' . ($row->link_value ?: '—');
                if ($row->link_section) {
                    $label .= ' / ' . $row->link_section;
                }

                return $label;
            case 'custom':
                return 'Custom: ' . \Illuminate\Support\Str::limit((string) $row->link_value, 40);
            case 'legacy':
            default:
                return 'Legacy (auto)';
        }
    }
}
