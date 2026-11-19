@extends('web.layouts.department')

@section('page-icon', 'fa-trophy')
@section('page-description', 'Staff and Student Achievements')

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

        <!-- Achievements Image -->
        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/achievements/' . $data->imageFile) }}"
                     alt="Department Achievements"
                     class="img-fluid rounded shadow-lg infrastructure-banner">
            </div>
        </div>
        @endif

        @php
            $staffAchievements = $staffAchievements ?? collect();
            if (!($staffAchievements instanceof \Illuminate\Support\Collection)) {
                $staffAchievements = collect($staffAchievements);
            }
            $studentAchievements = is_array($studentAchievements ?? null) ? $studentAchievements : [];
            $tableAchievements = is_array($studentAchievementsTableFormat ?? null) ? $studentAchievementsTableFormat : [];
            $appreciationList = is_array($studentAchievementsAppeciationList ?? null) ? $studentAchievementsAppeciationList : [];
        @endphp

        <!-- Staff Achievements -->
        @if($staffAchievements->isNotEmpty())
        <div class="row mb-5">
            <div class="col-12">
                <div class="achievements-section">
                    <div class="text-center mb-5">
                        <h2 class="section-title fw-bold text-primary mb-3 animate-fade-in">
                            <i class="fas fa-users me-3"></i> Staff Achievements
                        </h2>
                        <div class="title-underline mx-auto"></div>
                    </div>
                    <div class="row g-4">
                        @foreach($staffAchievements as $index => $achievement)
                        @php
                            $text = is_object($achievement)
                                ? ($achievement->description ?? $achievement->title ?? '')
                                : (is_string($achievement) ? $achievement : ($achievement['description'] ?? $achievement['title'] ?? ''));
                        @endphp
                        <div class="col-lg-6">
                            <div class="achievement-item p-4 border-0 rounded-3 shadow-sm h-100 animate-fade-in-up" style="animation-delay: {{ $index * 0.1 }}s;">
                                <div class="achievement-content">
                                    <div class="achievement-header d-flex align-items-center mb-3">
                                        <span class="achievement-number badge bg-primary rounded-circle p-3 me-3 fw-bold">{{ $index + 1 }}</span>
                                    </div>
                                    <div class="achievement-text">
                                        <p class="text-muted lh-lg mb-0">{{ $text }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Student Achievements -->
        @if(count($studentAchievements) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="achievements-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-graduation-cap text-success"></i> Student Achievements
                    </h2>
                    <div class="achievements-timeline">
                        @foreach($studentAchievements as $yearData)
                            @if(is_array($yearData) && isset($yearData['year']))
                            <div class="year-section mb-4">
                                <h3 class="year-title">
                                    <i class="fa fa-calendar text-info"></i> {{ $yearData['year'] }}
                                </h3>
                                @if(isset($yearData['achievements']) && is_array($yearData['achievements']))
                                <div class="achievements-list">
                                    @foreach($yearData['achievements'] as $achievement)
                                    <div class="achievement-item p-3 mb-2 border-left border-success">
                                        <div class="achievement-content">
                                            @if(!empty($achievement['title']))
                                            <h5 class="achievement-title">{{ $achievement['title'] }}</h5>
                                            @endif
                                            @if(!empty($achievement['description']))
                                            <p class="achievement-description mb-0">{!! $achievement['description'] !!}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Student Achievements Table Format -->
        @if(count($tableAchievements) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="achievements-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-table text-primary"></i> Student Achievements (Table)
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Student Name</th>
                                    <th>Event Nature</th>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Organized By</th>
                                    <th>Awards</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tableAchievements as $yearData)
                                    @if(is_array($yearData) && isset($yearData['year']))
                                        @if(isset($yearData['achievements']) && is_array($yearData['achievements']))
                                            @foreach($yearData['achievements'] as $achievement)
                                            <tr>
                                                <td>{{ $yearData['year'] }}</td>
                                                <td>{{ $achievement['studentName'] ?? '-' }}</td>
                                                <td>{{ $achievement['eventNature'] ?? '-' }}</td>
                                                <td>{{ $achievement['eventName'] ?? '-' }}</td>
                                                <td>{{ $achievement['periodDate'] ?? '-' }}</td>
                                                <td>{{ $achievement['organizedBy'] ?? '-' }}</td>
                                                <td>
                                                    <span class="badge badge-success">{{ $achievement['awards'] ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Student Achievements Appreciation List -->
        @if(count($appreciationList) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="achievements-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-star text-warning"></i> Student Appreciation List
                    </h2>
                    <div class="appreciation-list">
                        @foreach($appreciationList as $yearData)
                            @if(is_array($yearData) && isset($yearData['year']))
                            <div class="year-section mb-4">
                                <h3 class="year-title">
                                    <i class="fa fa-calendar text-info"></i> {{ $yearData['year'] }}
                                </h3>
                                @if(isset($yearData['appreciations']) && is_array($yearData['appreciations']))
                                <div class="row g-4 appreciations-grid">
                                    @foreach($yearData['appreciations'] as $appreciation)
                                    <div class="col-lg-6">
                                        <div class="appreciation-item p-3 border rounded bg-light h-100">
                                            <div class="appreciation-content">
                                                @if(!empty($appreciation['studentName']))
                                                <h5 class="appreciation-student">
                                                    <i class="fa fa-user text-primary"></i> {{ $appreciation['studentName'] }}
                                                </h5>
                                                @endif
                                                <div class="appreciation-details small text-muted">
                                                    @if(!empty($appreciation['yearOfStudent']))
                                                    <div><strong>Year:</strong> {{ $appreciation['yearOfStudent'] }}</div>
                                                    @endif
                                                    @if(!empty($appreciation['eventName']))
                                                    <div><strong>Event:</strong> {{ $appreciation['eventName'] }}</div>
                                                    @endif
                                                    @if(!empty($appreciation['projectName']))
                                                    <div><strong>Project:</strong> {{ $appreciation['projectName'] }}</div>
                                                    @endif
                                                    @if(!empty($appreciation['supervisor']))
                                                    <div><strong>Supervisor:</strong> {{ $appreciation['supervisor'] }}</div>
                                                    @endif
                                                    @if(!empty($appreciation['status']))
                                                    <div><strong>Status:</strong> {{ $appreciation['status'] }}</div>
                                                    @endif
                                                    @if(!empty($appreciation['achievement']))
                                                    <div><strong>Achievement:</strong> {{ $appreciation['achievement'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- No Data Message -->
        @if($staffAchievements->isEmpty() && count($studentAchievements) === 0 && count($tableAchievements) === 0 && count($appreciationList) === 0)
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <i class="fa fa-trophy fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Achievements Available</h3>
                    <p class="text-muted">Achievement information will be updated soon.</p>
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
