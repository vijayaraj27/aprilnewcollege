@extends('admin.layouts.master')
@section('title', $title)
@section('content')

<div class="main-body">
    <div class="page-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('modal_add') }} {{ $title }}</h5>
                    </div>
                    <div class="card-block">
                        <a href="{{ route($route.'.index', $filter) }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('btn_back') }}</a>
                        <a href="{{ route($route.'.create', $filter) }}" class="btn btn-info"><i class="fas fa-sync-alt"></i> {{ __('btn_refresh') }}</a>
                    </div>

                    <form class="needs-validation" novalidate action="{{ route($route.'.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    @if(!empty($selected_department_id) && Auth::user()->is_admin != '1')
                    <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
                    @endif
                    <div class="card-block">
                      <div class="row">
                        @if(Auth::user()->is_admin == '1' && !empty($departments))
                        <div class="form-group col-md-6">
                            <label for="department_id">Department <span>*</span></label>
                            <select class="form-control" name="department_id" id="department_id" required>
                                <option value="">{{ __('select') }}</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" @if(old('department_id', $selected_department_id ?? '') == $department->id) selected @endif>{{ $department->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="form-group col-md-6">
                            <label for="title">{{ __('field_title') }} <span>*</span></label>
                            <input type="text" class="form-control" name="title" id="title" value="{{ old('title') }}" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label for="image">Image</label>
                            <input type="file" class="form-control" name="image" id="image" accept="image/*">
                            <small class="form-text text-muted">Recommended landscape image (approx. 800×600). Aspect ratio is preserved.</small>
                        </div>

                        <div class="form-group col-md-12">
                            <label for="description">Description</label>
                            <textarea class="form-control texteditor" name="description" id="description" rows="5">{{ old('description') }}</textarea>
                        </div>
                      </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> {{ __('btn_save') }}</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
