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
                                        <th>Photo</th>
                                        <th>Name</th>
                                        @if($type === 'teaching')
                                        <th>Designation</th>
                                        <th>Email</th>
                                        <th>AU-FIN</th>
                                        <th>AICTE ID</th>
                                        @else
                                        <th>Qualification</th>
                                        <th>Designation</th>
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
                                        <td>
                                            @if(!empty($row->photo) && is_file(public_path('uploads/'.$path.'/'.$row->photo)))
                                            <img style="width:48px;height:60px;object-fit:cover;object-position:center top;border-radius:4px;" src="{{ asset('uploads/'.$path.'/'.$row->photo) }}" alt="">
                                            @else
                                            <img style="width:48px;height:60px;object-fit:cover;border-radius:4px;opacity:.85;" src="{{ asset('uploads/images/default-faculty-avatar.svg') }}" alt="No photo">
                                            @endif
                                        </td>
                                        <td>{{ $row->name }}</td>
                                        @if($type === 'teaching')
                                        <td>{{ $row->designation ?: '—' }}</td>
                                        <td>{{ $row->email ?: '—' }}</td>
                                        <td>{{ $row->au_fin ?: '—' }}</td>
                                        <td>{{ $row->aicte_id ?: '—' }}</td>
                                        @else
                                        <td>{{ $row->qualification ?: '—' }}</td>
                                        <td>{{ $row->designation ?: '—' }}</td>
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
