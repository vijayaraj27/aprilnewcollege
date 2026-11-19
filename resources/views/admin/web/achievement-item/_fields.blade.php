@php
    $isEdit = isset($row);
@endphp

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

@if($type !== 'staff')
<div class="form-group col-md-6">
    <label for="year">Year <span>*</span></label>
    <input type="text" class="form-control" name="year" id="year" value="{{ old('year', $isEdit ? $row->year : '') }}" placeholder="e.g., 2023-24" required>
    <small class="form-text text-muted">Prefer YYYY-YY (e.g., 2023-24). Free text is allowed.</small>
</div>
@endif

@if($type === 'staff')
<div class="form-group col-md-12">
    <label for="description">Achievement <span>*</span></label>
    <textarea class="form-control" name="description" id="description" rows="4" required>{{ old('description', $isEdit ? $row->description : '') }}</textarea>
</div>
@elseif($type === 'student')
<div class="form-group col-md-6">
    <label for="title">{{ __('field_title') }}</label>
    <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $isEdit ? $row->title : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="image">Image</label>
    <input type="file" class="form-control" name="image" id="image" accept="image/*">
    <small class="form-text text-muted">Recommended landscape image (approx. 800×600). Aspect ratio is preserved.</small>
    @if($isEdit && !empty($row->imageUrl()))
    <img class="mt-2" style="width:120px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;" src="{{ $row->imageUrl() }}" alt="">
    @endif
</div>
<div class="form-group col-md-12">
    <label for="description">Description</label>
    <textarea class="form-control texteditor" name="description" id="description" rows="5">{{ old('description', $isEdit ? $row->description : '') }}</textarea>
</div>
@elseif($type === 'student-table')
<div class="form-group col-md-6">
    <label for="student_name">Student Name <span>*</span></label>
    <input type="text" class="form-control" name="student_name" id="student_name" value="{{ old('student_name', $isEdit ? $row->student_name : '') }}" required>
</div>
<div class="form-group col-md-6">
    <label for="event_nature">Event Nature</label>
    <input type="text" class="form-control" name="event_nature" id="event_nature" value="{{ old('event_nature', $isEdit ? $row->event_nature : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="event_name">Event Name</label>
    <input type="text" class="form-control" name="event_name" id="event_name" value="{{ old('event_name', $isEdit ? $row->event_name : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="period_date">Period / Date</label>
    <input type="text" class="form-control" name="period_date" id="period_date" value="{{ old('period_date', $isEdit ? $row->period_date : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="organized_by">Organized By</label>
    <input type="text" class="form-control" name="organized_by" id="organized_by" value="{{ old('organized_by', $isEdit ? $row->organized_by : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="awards">Awards</label>
    <input type="text" class="form-control" name="awards" id="awards" value="{{ old('awards', $isEdit ? $row->awards : '') }}">
</div>
@elseif($type === 'student-appreciation')
<div class="form-group col-md-6">
    <label for="student_name">Student Name <span>*</span></label>
    <input type="text" class="form-control" name="student_name" id="student_name" value="{{ old('student_name', $isEdit ? $row->student_name : '') }}" required>
</div>
<div class="form-group col-md-6">
    <label for="year_of_student">Year of Student</label>
    <input type="text" class="form-control" name="year_of_student" id="year_of_student" value="{{ old('year_of_student', $isEdit ? $row->year_of_student : '') }}" placeholder="e.g., II / III">
</div>
<div class="form-group col-md-6">
    <label for="event_name">Event Name</label>
    <input type="text" class="form-control" name="event_name" id="event_name" value="{{ old('event_name', $isEdit ? $row->event_name : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="project_name">Project Name</label>
    <input type="text" class="form-control" name="project_name" id="project_name" value="{{ old('project_name', $isEdit ? $row->project_name : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="supervisor">Supervisor</label>
    <input type="text" class="form-control" name="supervisor" id="supervisor" value="{{ old('supervisor', $isEdit ? $row->supervisor : '') }}">
</div>
<div class="form-group col-md-6">
    <label for="result_status">Result / Status</label>
    <input type="text" class="form-control" name="result_status" id="result_status" value="{{ old('result_status', $isEdit ? $row->result_status : '') }}">
</div>
@endif

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
