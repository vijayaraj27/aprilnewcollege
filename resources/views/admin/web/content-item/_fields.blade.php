@php $isEdit = isset($row); @endphp

@if(Auth::user()->is_admin == '1' && !empty($departments))
<div class="form-group col-md-6">
    <label for="department_id">Department <span>*</span></label>
    <select class="form-control" name="department_id" id="department_id" required>
        <option value="">{{ __('select') }}</option>
        @foreach($departments as $department)
        <option value="{{ $department->id }}" @if(old('department_id', $isEdit ? $row->department_id : ($selected_department_id ?? '')) == $department->id) selected @endif>{{ $department->title }}</option>
        @endforeach
    </select>
</div>
@endif

@if(!empty($uses_year))
<div class="form-group col-md-6">
    <label for="year">Year <span>*</span></label>
    <input type="text" class="form-control" name="year" id="year" value="{{ old('year', $isEdit ? $row->year : '') }}" placeholder="e.g., 2023-24" required>
</div>
@endif

<div class="form-group col-md-12">
    <label for="title">{{ __('field_title') }}</label>
    @if(empty($title_on_frontend))
    <small class="d-block text-muted mb-1">Optional — not displayed on the public website. Leave empty if you do not need an admin-only label.</small>
    @endif
    <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $isEdit ? $row->title : '') }}" @if(empty($title_on_frontend)) placeholder="Admin label only (optional)" @endif>
</div>

@if(!empty($show_description))
<div class="form-group col-md-12">
    <label for="description">Description @if(!empty($description_required))<span>*</span>@endif</label>
    <textarea class="form-control" name="description" id="description" rows="5" @if(!empty($description_required)) required @endif>{{ old('description', $isEdit ? $row->description : '') }}</textarea>
</div>
@endif

@foreach($fields as $field)
@php
    $name = $field['name'];
    $old = old('meta.'.$name, $isEdit ? $row->metaGet($name) : '');
    $fieldType = $field['type'] ?? 'text';
    $isWide = in_array($fieldType, ['textarea', 'editor', 'image'], true) || !empty($field['wide']);
@endphp
<div class="form-group {{ $isWide ? 'col-md-12' : 'col-md-6' }}">
    <label for="meta_{{ $name }}">{{ $field['label'] }} @if(!empty($field['required']))<span>*</span>@endif</label>
    @if(!empty($field['hint']))
    <small class="d-block text-muted mb-1">{{ $field['hint'] }}</small>
    @endif
    @if($fieldType === 'editor')
    <textarea class="form-control texteditor" name="meta[{{ $name }}]" id="meta_{{ $name }}" rows="6" @if(!empty($field['required'])) required @endif>{{ $old }}</textarea>
    @elseif($fieldType === 'textarea')
    <textarea class="form-control" name="meta[{{ $name }}]" id="meta_{{ $name }}" rows="{{ $field['rows'] ?? 6 }}" @if(!empty($field['required'])) required @endif>{{ $old }}</textarea>
    @elseif($fieldType === 'image')
    <input type="file" class="form-control" name="meta_file[{{ $name }}]" id="meta_{{ $name }}" accept="image/*" @if(!empty($field['required']) && !$isEdit) required @endif>
    @if($isEdit && !empty($old))
        @if(\Illuminate\Support\Str::startsWith($old, ['http://', 'https://']))
        <img class="mt-2" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;" src="{{ $old }}" alt="">
        @elseif(is_file(public_path('uploads/'.$path.'/'.$old)))
        <img class="mt-2" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;" src="{{ asset('uploads/'.$path.'/'.$old) }}" alt="">
        @elseif(is_file(public_path('uploads/events/'.$old)))
        <img class="mt-2" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;" src="{{ asset('uploads/events/'.$old) }}" alt="">
        @else
        <small class="d-block text-muted mt-1">Current file: {{ $old }}</small>
        @endif
    @endif
    @else
    <input type="{{ $fieldType }}" class="form-control" name="meta[{{ $name }}]" id="meta_{{ $name }}" value="{{ $old }}" @if(!empty($field['required'])) required @endif>
    @endif
</div>
@endforeach

<div class="form-group col-md-6">
    <label for="sort_order">Sort Order</label>
    <input type="number" class="form-control" name="sort_order" id="sort_order" value="{{ old('sort_order', $isEdit ? $row->sort_order : 0) }}">
</div>

@if($isEdit)
<div class="form-group col-md-6">
    <label for="status">{{ __('select_status') }}</label>
    <select class="form-control" name="status" id="status">
        <option value="1" @if(old('status', $row->status) == 1) selected @endif>{{ __('status_active') }}</option>
        <option value="0" @if(old('status', $row->status) == 0) selected @endif>{{ __('status_inactive') }}</option>
    </select>
</div>
@endif
