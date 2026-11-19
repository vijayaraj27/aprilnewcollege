@extends('admin.layouts.master')
@section('title', $title)
@section('content')

<!-- Start Content-->
<div class="main-body">
    <div class="page-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    @if(($section ?? 'basic') === 'basic')
                    <div class="card-header">
                        <h3 class="bold">Section Events</h3>
                    </div>

                    <div class="card-block">
                        <form id="eventsForm" class="needs-validation" method="POST"
                            action="{{ route($route . '.store', ['departmentId' => $departmentId, 'section' => $section]) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="departmentId" value="{{ $departmentId }}">
                            <input type="hidden" name="section" value="{{ $section }}">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="photo">{{ __('field_photo') }}:
                                        <span>{{ __('image_size', ['height' => 400, 'width' => 400]) }}</span>
                                    </label>
                                    <input type="file" class="form-control" name="imageFile" placeholder="Image File">
                                    @if(!empty($row->imageFile))
                                    <img alt="Section Events Image" width="70" height="70" class="mt-2"
                                        src="{{ $baseurl . 'uploads/events/' . $row->imageFile }}" />
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Title</label>
                                    <input type="text" class="form-control" name="title" placeholder="Title" required
                                        value="{{ isset($row->title) ? $row->title : '' }}">
                                </div>
                                <div class="form-group col-md-12">
                                    <textarea class="form-control texteditor" name="description"
                                        placeholder="Description"
                                        required>{{ isset($row->description) ? $row->description : '' }}</textarea>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success">Save Events Section</button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Content-->

@endsection
