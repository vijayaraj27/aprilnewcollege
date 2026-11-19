@extends('web.layouts.department')

@section('page-icon', 'fa-building')
@section('page-description', 'Modern Infrastructure and Facilities')

@section('main-content')
<section class="section-padding">
    <div class="container-fluid px-3 px-lg-4">
        <!-- Page Title and Description -->
        @if(!empty($data->title))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <h1 class="page-title">{{ $data->title }}</h1>
                @if(!empty($data->description))
                <div class="page-description">{!! $data->description !!}</div>
                @endif
            </div>
        </div>
        @endif

        <!-- Infrastructure Image -->
        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/infrastructures/' . $data->imageFile) }}" 
                     alt="Department Infrastructure" 
                     class="img-fluid rounded shadow-lg infrastructure-banner">
            </div>
        </div>
        @endif

        @php
            $buildings = $buildings ?? collect();
            if (!($buildings instanceof \Illuminate\Support\Collection)) {
                $buildings = collect($buildings);
            }
            $facilities = [];
            if (!empty($data->facilities)) {
                $facilities = is_string($data->facilities) ? json_decode($data->facilities, true) : $data->facilities;
                $facilities = is_array($facilities) ? $facilities : [];
            }
        @endphp

        <!-- Buildings -->
        @if($buildings->isNotEmpty())
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="infrastructure-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-building text-primary"></i> Buildings & Facilities
                    </h2>
                    <div class="buildings-grid">
                        @foreach($buildings as $index => $building)
                        @php
                            $buildingTitle = is_object($building) ? ($building->title ?? '') : ($building['title'] ?? '');
                            $buildingDescription = is_object($building) ? ($building->description ?? '') : ($building['description'] ?? '');
                            $buildingImageUrl = null;
                            if (is_object($building) && method_exists($building, 'imageUrl')) {
                                $buildingImageUrl = $building->imageUrl();
                            } elseif (is_array($building)) {
                                $legacyImage = $building['image'] ?? ($building['imageFile'] ?? null);
                                if (!empty($legacyImage)) {
                                    $buildingImageUrl = asset('uploads/infrastructure-items/' . $legacyImage);
                                }
                            }
                        @endphp
                        <div class="building-item p-4 mb-4 border rounded">
                            <div class="building-content">
                                <div class="building-header">
                                    <h4 class="building-title">
                                        <i class="fa fa-university text-primary"></i>
                                        {{ $buildingTitle !== '' ? $buildingTitle : ('Building ' . ($index + 1)) }}
                                    </h4>
                                </div>
                                <div class="building-details">
                                    @if(!empty($buildingDescription))
                                    <p class="building-description">{!! $buildingDescription !!}</p>
                                    @endif
                                    @if(!empty($buildingImageUrl))
                                    <div class="building-image mt-3">
                                        <img src="{{ $buildingImageUrl }}"
                                             alt="{{ $buildingTitle !== '' ? $buildingTitle : 'Building Image' }}"
                                             class="img-fluid rounded building-thumbnail">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Facilities -->
        @if(count($facilities) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="infrastructure-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-cogs text-success"></i> Facilities
                    </h2>
                    <div class="facilities-grid">
                        @foreach($facilities as $index => $facility)
                        <div class="facility-item p-4 mb-3 border rounded">
                            <div class="facility-content">
                                <div class="facility-header">
                                    <h5 class="facility-title">
                                        <i class="fa fa-wrench text-success"></i>
                                        {{ $facility['name'] ?? 'Facility ' . ($index + 1) }}
                                    </h5>
                                </div>
                                <div class="facility-details">
                                    @if(!empty($facility['description']))
                                    <p class="facility-description">{!! $facility['description'] !!}</p>
                                    @endif
                                    @if(!empty($facility['equipment']))
                                    <div class="equipment-list">
                                        <strong>Equipment:</strong>
                                        <ul class="equipment-items">
                                            @foreach($facility['equipment'] as $equipment)
                                            <li>{{ $equipment }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- No Data Message -->
        @if($buildings->isEmpty() && count($facilities) === 0)
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <i class="fa fa-building fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Infrastructure Information Available</h3>
                    <p class="text-muted">Infrastructure information will be updated soon.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
/* Page-specific styles can be added here */
</style>
@endpush

@push('scripts')
<script>
// Page-specific scripts can be added here
</script>
@endpush