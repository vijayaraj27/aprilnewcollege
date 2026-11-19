<div id="showModal-{{ $row->id }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('modal_view') }} {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @if(!empty($uses_year))
                <p><mark class="text-primary">Year:</mark> {{ $row->year ?: '—' }}</p><hr/>
                @endif
                @if(!empty($title_on_frontend))
                <p><mark class="text-primary">{{ __('field_title') }}:</mark> {{ $row->title ?: '—' }}</p><hr/>
                @elseif(!empty($row->title))
                <p><mark class="text-primary">{{ __('field_title') }} (admin only):</mark> {{ $row->title }}</p><hr/>
                @endif
                @if(!empty($show_description))
                <p><mark class="text-primary">Description:</mark></p>
                <div style="white-space: pre-wrap;">{{ $row->description ?: '—' }}</div><hr/>
                @endif
                @foreach($fields as $field)
                @php $fieldType = $field['type'] ?? 'text'; $value = $row->metaGet($field['name']); @endphp
                <p><mark class="text-primary">{{ $field['label'] }}:</mark></p>
                @if($fieldType === 'image')
                    @if(!empty($value) && \Illuminate\Support\Str::startsWith($value, ['http://', 'https://']))
                    <img class="mb-2" style="max-width:280px;max-height:180px;object-fit:cover;border-radius:6px;" src="{{ $value }}" alt="">
                    @elseif(!empty($value) && is_file(public_path('uploads/'.$path.'/'.$value)))
                    <img class="mb-2" style="max-width:280px;max-height:180px;object-fit:cover;border-radius:6px;" src="{{ asset('uploads/'.$path.'/'.$value) }}" alt="">
                    @elseif(!empty($value) && is_file(public_path('uploads/events/'.$value)))
                    <img class="mb-2" style="max-width:280px;max-height:180px;object-fit:cover;border-radius:6px;" src="{{ asset('uploads/events/'.$value) }}" alt="">
                    @else
                    <span>—</span>
                    @endif
                @else
                <span style="white-space: pre-wrap;">{{ $value ?: '—' }}</span>
                @endif
                <hr/>
                @endforeach
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
