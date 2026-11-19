@extends('admin.layouts.master')
@section('title', $title)
@section('content')

<div class="main-body">
    <div class="page-wrapper">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ $title }} {{ __('list') }}</h5>
                        @if(!empty($selected_department))
                        <small class="text-muted">{{ $selected_department->title }}</small>
                        @endif
                    </div>
                    <div class="card-block">
                        @can($access.'-create')
                        <a href="{{ route($route.'.create', $filter) }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('btn_add_new') }}</a>
                        @endcan
                        <a href="{{ route($route.'.index', $filter) }}" class="btn btn-info"><i class="fas fa-sync-alt"></i> {{ __('btn_refresh') }}</a>
                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table id="export-table" class="display table nowrap table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        @if($type !== 'staff')
                                        <th>Year</th>
                                        @endif
                                        @if($type === 'student')
                                        <th>Image</th>
                                        <th>{{ __('field_title') }}</th>
                                        <th>Description</th>
                                        @elseif($type === 'staff')
                                        <th>Achievement</th>
                                        @elseif($type === 'student-table')
                                        <th>Student</th>
                                        <th>Event</th>
                                        <th>Awards</th>
                                        @else
                                        <th>Student</th>
                                        <th>Event</th>
                                        <th>Status</th>
                                        @endif
                                        <th>Sort</th>
                                        <th>{{ __('field_status') }}</th>
                                        <th>{{ __('field_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  @foreach( $rows as $key => $row )
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        @if($type !== 'staff')
                                        <td>{{ $row->year ?: '—' }}</td>
                                        @endif
                                        @if($type === 'student')
                                        <td>
                                            @if(!empty($row->imageUrl()))
                                            <img style="width:80px;height:60px;object-fit:cover;border-radius:4px;" src="{{ $row->imageUrl() }}" alt="">
                                            @else
                                            —
                                            @endif
                                        </td>
                                        <td>{{ $row->title ?: '—' }}</td>
                                        <td>{!! str_limit(strip_tags($row->description), 80, ' ...') !!}</td>
                                        @elseif($type === 'staff')
                                        <td>{!! str_limit(strip_tags($row->description), 120, ' ...') !!}</td>
                                        @elseif($type === 'student-table')
                                        <td>{{ $row->student_name ?: '—' }}</td>
                                        <td>{{ $row->event_name ?: ($row->event_nature ?: '—') }}</td>
                                        <td>{{ $row->awards ?: '—' }}</td>
                                        @else
                                        <td>{{ $row->student_name ?: '—' }}</td>
                                        <td>{{ $row->event_name ?: ($row->project_name ?: '—') }}</td>
                                        <td>{{ $row->result_status ?: '—' }}</td>
                                        @endif
                                        <td>{{ $row->sort_order }}</td>
                                        <td>
                                            @if( $row->status == 1 )
                                            <span class="badge badge-pill badge-success">{{ __('status_active') }}</span>
                                            @else
                                            <span class="badge badge-pill badge-danger">{{ __('status_inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-icon btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#showModal-{{ $row->id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @include($view.'.show')

                                            @can($access.'-edit')
                                            <a href="{{ route($route.'.edit', array_merge([$row->id], $filter)) }}" class="btn btn-icon btn-primary btn-sm">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            @endcan

                                            @can($access.'-delete')
                                            <button type="button" class="btn btn-icon btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $row->id }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @include('admin.layouts.inc.delete')
                                            @endcan
                                        </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
