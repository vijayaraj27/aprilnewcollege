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
                        <a href="{{ route($route.'.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> {{ __('btn_back') }}
                        </a>
                    </div>

                    <form class="needs-validation" novalidate action="{{ route($route.'.store') }}" method="post">
                        @csrf
                        <div class="card-block">
                            <div class="row">
                                @include('admin.web.site-menu._form_fields')
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> {{ __('btn_save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
