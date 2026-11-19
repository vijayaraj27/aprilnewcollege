@extends('web.layouts.department')

@section('page-icon', 'fa-calendar-alt')
@section('page-description', 'Upcoming and Past Department Events')

@section('main-content')
<section class="section-padding">
    <div class="container-fluid px-3 px-lg-4">
        <!-- Page Title and Description -->
        @if(!empty($data->title))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <h1 class="page-title">{{ $data->title }}</h1>
                @if(!empty($data->description))
                <p class="page-description">{!! $data->description !!}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Event Image -->
        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/events/' . $data->imageFile) }}"
                     alt="Department Events"
                     class="img-fluid rounded shadow-lg event-banner">
            </div>
        </div>
        @endif

        @php
            $eventDetails = is_array($eventDetails ?? null) ? $eventDetails : [];

            $eventImageUrl = function ($value) {
                if (empty($value) || !is_string($value)) {
                    return null;
                }
                if (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://'])) {
                    return $value;
                }
                if (is_file(public_path('uploads/event-items/' . $value))) {
                    return asset('uploads/event-items/' . $value);
                }
                if (is_file(public_path('uploads/events/' . $value))) {
                    return asset('uploads/events/' . $value);
                }
                return asset('uploads/event-items/' . $value);
            };
        @endphp

        @if(count($eventDetails) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="events-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-calendar text-primary"></i> Department Events
                    </h2>
                    <div class="events-list">
                        @foreach($eventDetails as $index => $event)
                        @php
                            $image1 = $eventImageUrl($event['image1'] ?? ($event['imageFile'] ?? null));
                            $image2 = $eventImageUrl($event['image2'] ?? null);
                            $hasImages = $image1 || $image2;
                        @endphp
                        <div class="event-card p-4 mb-4 border rounded">
                            @if($hasImages)
                            <div class="event-images mb-3">
                                <div class="row g-3">
                                    @if($image1)
                                    <div class="{{ $image2 ? 'col-md-6' : 'col-md-12' }}">
                                        <img src="{{ $image1 }}"
                                             alt="Event image"
                                             class="img-fluid rounded event-photo w-100">
                                    </div>
                                    @endif
                                    @if($image2)
                                    <div class="{{ $image1 ? 'col-md-6' : 'col-md-12' }}">
                                        <img src="{{ $image2 }}"
                                             alt="Event image"
                                             class="img-fluid rounded event-photo w-100">
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if(!empty($event['description']))
                            <div class="event-description">
                                {!! nl2br(e($event['description'])) !!}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <i class="fa fa-calendar fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Events Available</h3>
                    <p class="text-muted">Event information will be updated soon.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
.events-section .event-card {
    background: #fff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}
.events-section .event-photo {
    max-height: 320px;
    object-fit: cover;
}
.events-section .event-description {
    color: #333;
    line-height: 1.7;
    font-size: 15px;
}
</style>
@endpush

@push('scripts')
<script>
// Page-specific scripts can be added here
</script>
@endpush
