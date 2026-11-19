@extends('admin.layouts.master')
@section('title', $title)
@section('content')

<!-- Start Content-->
<div class="main-body">
    <div class="page-wrapper">
        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- [ Card ] start -->
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('modal_add') }} {{ $title }}</h5>
                    </div>
                    <div class="card-block">
                        <a href="{{ route($route.'.index', $filter) }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('btn_back') }}</a>

                        <a href="{{ route($route.'.create', $filter) }}" class="btn btn-info"><i class="fas fa-sync-alt"></i> {{ __('btn_refresh') }}</a>
                    </div>

                    <form class="needs-validation" novalidate action="{{ route($route.'.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    @if(!empty($selected_department_id) && Auth::user()->is_admin != '1')
                    <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
                    @endif
                    <div class="card-block">
                      <div class="row">
                        <!-- Form Start -->
                        @if(Auth::user()->is_admin == '1' && !empty($departments))
                        <div class="form-group col-md-6">
                            <label for="department_id">Department <span>*</span></label>
                            <select class="form-control" name="department_id" id="department_id" required>
                                <option value="">{{ __('select') }}</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" @if(old('department_id', $selected_department_id ?? '') == $department->id) selected @endif>{{ $department->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                              {{ __('required_field') }} Department
                            </div>
                        </div>
                        @endif

                        <div class="form-group col-md-6">
                            <label for="title">{{ __('field_title') }}</label>
                            <input type="text" class="form-control" name="title" id="title" value="{{ old('title') }}" placeholder="Optional short label (e.g. PEO1)">
                        </div>

                        <div class="form-group col-md-6">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}">
                        </div>

                        <div class="form-group col-md-12">
                            <label for="description">Description <span>*</span></label>
                            <textarea class="form-control" name="description" id="description" rows="5" required>{{ old('description') }}</textarea>
                            <div class="invalid-feedback">
                              {{ __('required_field') }} Description
                            </div>
                        </div>
                        <!-- Form End -->
                      </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> {{ __('btn_save') }}</button>
                    </div>
                    </form>
                </div>
            </div>
            <!-- [ Card ] end -->
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- End Content-->

@endsection
