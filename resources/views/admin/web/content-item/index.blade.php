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
                                        @if(!empty($uses_year))
                                        <th>Year</th>
                                        @endif
                                        @if(!empty($title_on_frontend))
                                        <th>{{ __('field_title') }}</th>
                                        @endif
                                        @if(!empty($show_description))
                                        <th>Description</th>
                                        @endif
                                        @foreach($fields as $field)
                                        <th>{{ $field['label'] }}</th>
                                        @endforeach
                                        <th>Sort</th>
                                        <th>{{ __('field_status') }}</th>
                                        <th>{{ __('field_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  @foreach( $rows as $key => $row )
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        @if(!empty($uses_year))
                                        <td>{{ $row->year ?: '—' }}</td>
                                        @endif
                                        @if(!empty($title_on_frontend))
                                        <td>{{ $row->title ?: '—' }}</td>
                                        @endif
                                        @if(!empty($show_description))
                                        <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($row->description ?: '—')), 80) }}</td>
                                        @endif
                                        @foreach($fields as $field)
                                        @php $fieldType = $field['type'] ?? 'text'; $value = $row->metaGet($field['name']); @endphp
                                        <td>
                                            @if($fieldType === 'image')
                                                @if(!empty($value) && \Illuminate\Support\Str::startsWith($value, ['http://', 'https://']))
                                                <img style="width:64px;height:48px;object-fit:cover;border-radius:4px;" src="{{ $value }}" alt="">
                                                @elseif(!empty($value) && is_file(public_path('uploads/'.$path.'/'.$value)))
                                                <img style="width:64px;height:48px;object-fit:cover;border-radius:4px;" src="{{ asset('uploads/'.$path.'/'.$value) }}" alt="">
                                                @elseif(!empty($value) && is_file(public_path('uploads/events/'.$value)))
                                                <img style="width:64px;height:48px;object-fit:cover;border-radius:4px;" src="{{ asset('uploads/events/'.$value) }}" alt="">
                                                @else
                                                —
                                                @endif
                                            @else
                                                {{ \Illuminate\Support\Str::limit((string) ($value ?: '—'), 80) }}
                                            @endif
                                        </td>
                                        @endforeach
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
                                            @include('admin.web.content-item.show')

                                            @can($access.'-edit')
                                            <a href="{{ route($route.'.edit', $row->id) }}?{{ http_build_query($filter) }}" class="btn btn-icon btn-primary btn-sm">
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
