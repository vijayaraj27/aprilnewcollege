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
                        <small class="text-muted">SuperAdmin only — manage {{ $location }} navigation</small>
                    </div>
                    <div class="card-block">
                        @can($access.'-create')
                        <a href="{{ route($route.'.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> {{ __('btn_add_new') }}
                        </a>
                        @endcan
                        <a href="{{ route($route.'.index') }}" class="btn btn-info">
                            <i class="fas fa-sync-alt"></i> {{ __('btn_refresh') }}
                        </a>
                    </div>

                    <div class="card-block">
                        <div class="table-responsive">
                            <table class="display table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th>Link</th>
                                        <th>Sort</th>
                                        <th>{{ __('field_status') }}</th>
                                        <th>{{ __('field_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($rows as $key => $row)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            @php $depth = (int) ($row->depth ?? 0); @endphp
                                            @if($depth > 0)
                                                <span class="text-muted">{{ str_repeat('— ', $depth) }}</span>
                                            @endif
                                            <strong>{{ $row->menu }}</strong>
                                        </td>
                                        <td>
                                            <small>
                                                @php
                                                    $desc = match ($row->link_type) {
                                                        'none' => 'None',
                                                        'page' => 'Page: '.($row->link_value ?: '—'),
                                                        'department' => 'Dept: '.($row->link_value ?: '—').($row->link_section ? ' / '.$row->link_section : ''),
                                                        'custom' => 'Custom: '.\Illuminate\Support\Str::limit((string) $row->link_value, 40),
                                                        default => 'Legacy (auto)',
                                                    };
                                                @endphp
                                                {{ $desc }}
                                                @if($row->open_in_new_tab)
                                                    <span class="badge badge-info">new tab</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>{{ $row->sort_order }}</td>
                                        <td>
                                            @if((int) $row->status === 0)
                                                <span class="badge badge-pill badge-success">Shown</span>
                                            @else
                                                <span class="badge badge-pill badge-danger">Hidden</span>
                                            @endif
                                        </td>
                                        <td>
                                            @can($access.'-edit')
                                            <a href="{{ route($route.'.edit', $row->id) }}" class="btn btn-icon btn-primary btn-sm">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can($access.'-delete')
                                            <button type="button" class="btn btn-icon btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $row->id }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @include('admin.layouts.inc.delete')
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No menu items yet.</td>
                                    </tr>
                                @endforelse
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
