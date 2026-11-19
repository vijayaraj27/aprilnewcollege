@extends('web.layouts.department')

@section('page-icon', 'fa-graduation-cap')
@section('page-description', 'Academic Curriculum and Syllabus')

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

        <!-- Syllabus Image -->
        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/syllabus/' . $data->imageFile) }}"
                     alt="Department Syllabus"
                     class="img-fluid rounded shadow-lg syllabus-banner">
            </div>
        </div>
        @endif

        <!-- Regulations -->
        @php
            $regulations = is_array($regulation ?? null) ? $regulation : [];
        @endphp
        @if(count($regulations) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="syllabus-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-gavel text-primary"></i> Regulations
                    </h2>
                    <div class="dept-card-grid">
                        @foreach($regulations as $index => $regulation)
                        <div class="regulation-item p-4 border rounded">
                            <div class="regulation-content">
                                <div class="regulation-header">
                                    <h4 class="regulation-title">
                                        <i class="fa fa-file-text text-primary"></i>
                                        {{ $regulation['Title'] ?? 'Regulation ' . ($index + 1) }}
                                    </h4>
                                    <div class="regulation-badges">
                                        @if(!empty($regulation['programType']))
                                        <span class="program-type badge badge-info">
                                            <i class="fa fa-graduation-cap"></i> {{ $regulation['programType'] }}
                                        </span>
                                        @endif
                                        @if(!empty($regulation['regulationType']))
                                        <span class="regulation-type badge badge-warning">
                                            <i class="fa fa-calendar"></i> {{ $regulation['regulationType'] }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($regulation['pdfLink']))
                                <div class="regulation-download">
                                    <a href="{{ $regulation['pdfLink'] }}"
                                       target="_blank"
                                       class="btn btn-primary download-btn">
                                        <i class="fa fa-download"></i> Download PDF
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Question Bank -->
        @php
            $questionBank = is_array($questionBank ?? null) ? $questionBank : [];
        @endphp
        @if(count($questionBank) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="syllabus-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-question-circle text-success"></i> Question Bank
                    </h2>
                    <div class="dept-card-grid">
                        @foreach($questionBank as $index => $question)
                        <div class="question-item p-4 border rounded">
                            <div class="question-content">
                                <div class="question-header">
                                    <h4 class="question-title">
                                        <i class="fa fa-file-text text-success"></i>
                                        {{ $question['Title'] ?? 'Question Bank ' . ($index + 1) }}
                                    </h4>
                                    @if(!empty($question['programType']))
                                    <span class="program-type badge badge-success">
                                        <i class="fa fa-graduation-cap"></i> {{ $question['programType'] }}
                                    </span>
                                    @endif
                                </div>
                                @if(!empty($question['pdfLink']))
                                <div class="question-download">
                                    <a href="{{ $question['pdfLink'] }}"
                                       target="_blank"
                                       class="btn btn-success download-btn">
                                        <i class="fa fa-download"></i> Download PDF
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Syllabus Details -->
        @php
            $syllabusDetails = is_array($syllabus ?? null) ? $syllabus : [];
        @endphp
        @if(count($syllabusDetails) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="syllabus-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-book text-warning"></i> Syllabus Details
                    </h2>
                    <div class="dept-card-grid">
                        @foreach($syllabusDetails as $index => $syllabus)
                        <div class="syllabus-item p-4 border rounded">
                            <div class="syllabus-content">
                                <div class="syllabus-header">
                                    <h4 class="syllabus-title">
                                        <i class="fa fa-file-text text-warning"></i>
                                        {{ $syllabus['Title'] ?? 'Syllabus ' . ($index + 1) }}
                                    </h4>
                                    @if(!empty($syllabus['programType']))
                                    <span class="program-type badge badge-warning">
                                        <i class="fa fa-graduation-cap"></i> {{ $syllabus['programType'] }}
                                    </span>
                                    @endif
                                </div>
                                @if(!empty($syllabus['pdfLink']))
                                <div class="syllabus-download">
                                    <a href="{{ $syllabus['pdfLink'] }}"
                                       target="_blank"
                                       class="btn btn-warning download-btn">
                                        <i class="fa fa-download"></i> Download PDF
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Syllabus Details (Alternative Structure) -->
        @if(!empty($data->syllabusDetails))
        @php
            $syllabusDetailsAlt = is_string($data->syllabusDetails) ? json_decode($data->syllabusDetails, true) : $data->syllabusDetails;
        @endphp
        @if(is_array($syllabusDetailsAlt) && count($syllabusDetailsAlt) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="syllabus-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-list-alt text-info"></i> Syllabus Information
                    </h2>
                    <div class="dept-card-grid">
                        @foreach($syllabusDetailsAlt as $index => $info)
                        <div class="syllabus-info-item p-4 border rounded">
                            <div class="syllabus-info-content">
                                <div class="syllabus-info-header">
                                    <h4 class="syllabus-info-title">
                                        <i class="fa fa-info-circle text-info"></i>
                                        {{ $info['title'] ?? 'Syllabus Info ' . ($index + 1) }}
                                    </h4>
                                </div>
                                @if(!empty($info['description']))
                                <p class="syllabus-info-description">{!! $info['description'] !!}</p>
                                @endif
                                @if(!empty($info['pdfLink']))
                                <div class="syllabus-info-download">
                                    <a href="{{ $info['pdfLink'] }}"
                                       target="_blank"
                                       class="btn btn-info download-btn">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        <!-- No Data Message -->
        @if(count($regulations) === 0 && count($questionBank) === 0 && count($syllabusDetails) === 0 && empty($data->syllabusDetails))
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <i class="fa fa-book fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Syllabus Information Available</h3>
                    <p class="text-muted">Syllabus information will be updated soon.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
/* True 3-column card grid — avoids Bootstrap col % + gap overflow */
.syllabus-section .dept-card-grid {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    width: 100%;
    max-width: 100%;
    margin: 0 !important;
    padding: 0 !important;
}
@media (max-width: 991.98px) {
    .syllabus-section .dept-card-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 575.98px) {
    .syllabus-section .dept-card-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
.syllabus-section .dept-card-grid > .regulation-item,
.syllabus-section .dept-card-grid > .question-item,
.syllabus-section .dept-card-grid > .syllabus-item,
.syllabus-section .dept-card-grid > .syllabus-info-item {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    min-width: 0;
    background: #ececec;
    box-shadow: 0 10px 30px 0 rgba(233, 233, 233, 0.5);
    border-radius: 8px !important;
}
.syllabus-section .download-btn {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    width: auto !important;
    max-width: 100%;
    padding: 6px 12px !important;
    font-size: 13px !important;
    font-weight: 600;
    line-height: 1.2 !important;
    border-radius: 4px !important;
    white-space: nowrap;
}
.syllabus-section .download-btn i {
    font-size: 13px;
    margin: 0 !important;
}
.syllabus-section .regulation-download,
.syllabus-section .question-download,
.syllabus-section .syllabus-download,
.syllabus-section .syllabus-info-download {
    margin-top: 12px;
}
.syllabus-section .regulation-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
</style>
@endpush
