@php
    $row = $row ?? null;
    $oldLinkType = old('link_type', $row->link_type ?? 'none');
    $oldLinkValue = old('link_value', $row->link_value ?? '');
    $oldLinkSection = old('link_section', $row->link_section ?? '');
@endphp

<div class="form-group col-md-8">
    <label for="menu">Label <span>*</span></label>
    <input type="text" class="form-control" name="menu" id="menu"
           value="{{ old('menu', $row->menu ?? '') }}" required>
    <div class="invalid-feedback">{{ __('required_field') }} Label</div>
</div>

<div class="form-group col-md-4">
    <label for="sort_order">Sort Order</label>
    <input type="number" min="0" class="form-control" name="sort_order" id="sort_order"
           value="{{ old('sort_order', $row->sort_order ?? 0) }}">
</div>

<div class="form-group col-md-6">
    <label for="parent_id">Parent Menu</label>
    <select class="form-control" name="parent_id" id="parent_id">
        <option value="0">— Top Level —</option>
        @foreach($parents as $parent)
            <option value="{{ $parent->id }}"
                @selected((string) old('parent_id', $row->parent_id ?? 0) === (string) $parent->id)>
                {{ $parent->label_indented }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-6">
    <label for="status">{{ __('select_status') }}</label>
    <select class="form-control" name="status" id="status" required>
        {{-- Menu table convention: 0 = active/shown, 1 = inactive/hidden --}}
        <option value="0" @selected((string) old('status', $row->status ?? 0) === '0')">Shown (Active)</option>
        <option value="1" @selected((string) old('status', $row->status ?? 0) === '1')">Hidden (Inactive)</option>
    </select>
</div>

<div class="form-group col-md-6">
    <label for="link_type">Link Type <span>*</span></label>
    <select class="form-control" name="link_type" id="link_type" required>
        @foreach($linkTypes as $value => $label)
            <option value="{{ $value }}" @selected($oldLinkType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-6">
    <label class="d-block">&nbsp;</label>
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" name="open_in_new_tab" id="open_in_new_tab"
            @checked((string) old('open_in_new_tab', $row->open_in_new_tab ?? 0) === '1')>
        <label class="form-check-label" for="open_in_new_tab">Open in new tab</label>
    </div>
</div>

<div class="form-group col-md-12 link-field" data-link-types="page" style="display:none;">
    <label for="page_slug">General Page</label>
    <select class="form-control" name="page_slug" id="page_slug">
        <option value="">— Select Page —</option>
        @foreach($pages as $page)
            <option value="{{ $page->slug }}"
                @selected($oldLinkType === 'page' && $oldLinkValue === $page->slug)>
                {{ $page->title }} ({{ $page->slug }})
            </option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-6 link-field" data-link-types="department" style="display:none;">
    <label for="department_slug">Department</label>
    <select class="form-control" name="department_slug" id="department_slug">
        <option value="">— Select Department —</option>
        @foreach($departments as $department)
            <option value="{{ $department->slug }}"
                @selected($oldLinkType === 'department' && $oldLinkValue === $department->slug)>
                {{ $department->title }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-6 link-field" data-link-types="department" style="display:none;">
    <label for="link_section">Department Section (optional)</label>
    <select class="form-control" name="link_section" id="link_section">
        <option value="">— Department Home / About —</option>
        @foreach($sections as $sectionKey => $sectionLabel)
            <option value="{{ $sectionKey }}" @selected($oldLinkSection === $sectionKey)>
                {{ $sectionLabel }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-12 link-field" data-link-types="custom" style="display:none;">
    <label for="custom_url">Custom URL / PDF</label>
    <input type="text" class="form-control" name="custom_url" id="custom_url"
           value="{{ $oldLinkType === 'custom' ? $oldLinkValue : '' }}"
           placeholder="https://... or /uploads/pdf/... or /icoact/">
    <small class="text-muted">Use full URL, site path, or local upload path.</small>
</div>

{{-- Hidden canonical fields submitted after JS sync --}}
<input type="hidden" name="link_value" id="link_value" value="{{ $oldLinkValue }}">

<script>
(function () {
    function syncLinkFields() {
        var typeEl = document.getElementById('link_type');
        if (!typeEl) return;
        var type = typeEl.value;
        document.querySelectorAll('.link-field').forEach(function (el) {
            var types = (el.getAttribute('data-link-types') || '').split(',');
            el.style.display = types.indexOf(type) !== -1 ? '' : 'none';
        });

        var linkValue = document.getElementById('link_value');
        if (!linkValue) return;
        if (type === 'page') {
            linkValue.value = (document.getElementById('page_slug') || {}).value || '';
        } else if (type === 'department') {
            linkValue.value = (document.getElementById('department_slug') || {}).value || '';
        } else if (type === 'custom') {
            linkValue.value = (document.getElementById('custom_url') || {}).value || '';
        } else {
            linkValue.value = '';
        }
    }

    ['link_type', 'page_slug', 'department_slug', 'custom_url'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', syncLinkFields);
            el.addEventListener('input', syncLinkFields);
        }
    });

    var form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', syncLinkFields);
    }

    syncLinkFields();
})();
</script>
