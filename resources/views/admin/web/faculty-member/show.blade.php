<div id="showModal-{{ $row->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('modal_view') }} {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @if(!empty($row->photo) && is_file(public_path('uploads/'.$path.'/'.$row->photo)))
                <img class="mb-3" style="width:120px;height:120px;object-fit:cover;" src="{{ asset('uploads/'.$path.'/'.$row->photo) }}" alt="">
                @endif
                <h4><mark class="text-primary">Name:</mark> {{ $row->name }}</h4>
                <hr/>
                @if($type === 'teaching')
                <p><mark class="text-primary">Designation:</mark> {{ $row->designation ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Email:</mark> {{ $row->email ?: '—' }}</p><hr/>
                <p><mark class="text-primary">AU-FIN:</mark> {{ $row->au_fin ?: '—' }}</p><hr/>
                <p><mark class="text-primary">AICTE ID:</mark> {{ $row->aicte_id ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Profile:</mark>
                    @if(!empty($row->profile_url))
                    <a href="{{ $row->profile_url }}" target="_blank">View</a>
                    @else
                    —
                    @endif
                </p><hr/>
                @else
                <p><mark class="text-primary">Qualification:</mark> {{ $row->qualification ?: '—' }}</p><hr/>
                <p><mark class="text-primary">Designation:</mark> {{ $row->designation ?: '—' }}</p><hr/>
                @endif
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
