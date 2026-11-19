<div id="showModal-{{ $row->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('modal_view') }} {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @if($type === 'student' && !empty($row->imageUrl()))
                <img class="mb-3 img-fluid rounded" style="max-height:220px;object-fit:cover;" src="{{ $row->imageUrl() }}" alt="">
                @endif

                @if($type !== 'staff')
                <p><mark class="text-primary">Year:</mark> {{ $row->year ?: '—' }}</p><hr/>
                @endif

                @if($type === 'staff')
                <p><mark class="text-primary">Achievement:</mark></p>
                <div>{!! nl2br(e($row->description)) !!}</div>
                @elseif($type === 'student')
                <h4><mark class="text-primary">{{ __('field_title') }}:</mark> {{ $row->title ?: '—' }}</h4>
                <hr/>
                <p><mark class="text-primary">Description:</mark></p>
                <div>{!! $row->description !!}</div>
                @elseif($type === 'student-table')
                <p><mark class="text-primary">Student:</mark> {{ $row->student_name ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Event Nature:</mark> {{ $row->event_nature ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Event Name:</mark> {{ $row->event_name ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Period / Date:</mark> {{ $row->period_date ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Organized By:</mark> {{ $row->organized_by ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Awards:</mark> {{ $row->awards ?: '—' }}</p>
                @else
                <p><mark class="text-primary">Student:</mark> {{ $row->student_name ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Year of Student:</mark> {{ $row->year_of_student ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Event:</mark> {{ $row->event_name ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Project:</mark> {{ $row->project_name ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Supervisor:</mark> {{ $row->supervisor ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Result / Status:</mark> {{ $row->result_status ?: '—' }}</p>
                @endif

                <hr/>
                <p><mark class="text-primary">Sort Order:</mark> {{ $row->sort_order }}</p><hr/>
                <p><mark class="text-primary">{{ __('field_status') }}:</mark>
                    @if( $row->status == 1 )
                    <span class="badge badge-pill badge-success">{{ __('status_active') }}</span>
                    @else
                    <span class="badge badge-pill badge-danger">{{ __('status_inactive') }}</span>
                    @endif
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> {{ __('btn_close') }}</button>
            </div>
        </div>
    </div>
</div>
