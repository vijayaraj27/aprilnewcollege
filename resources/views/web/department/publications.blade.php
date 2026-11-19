@extends('web.layouts.department')

@section('page-icon', 'fa-file-alt')
@section('page-description', 'Research Publications and Papers')

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

        <!-- Publication Image -->
        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/publications/' . $data->imageFile) }}"
                     alt="Department Publications"
                     class="img-fluid rounded shadow-lg publication-banner">
            </div>
        </div>
        @endif

        <!-- Patents -->
        @php
            $patents = is_array($patent ?? null) ? $patent : [];
        @endphp
        @if(is_array($patents) && count($patents) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="publications-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-certificate text-warning"></i> Patents
                    </h2>
                    <div class="publications-grid">
                        @foreach($patents as $index => $patent)
                        <div class="publication-item p-4 mb-4 border rounded">
                            <div class="publication-content">
                                <div class="publication-header">
                                    <h4 class="publication-title">
                                        <i class="fa fa-lightbulb-o text-warning"></i>
                                        {{ $patent['titleOfThePatents'] ?? 'Patent ' . ($index + 1) }}
                                    </h4>
                                    @if(!empty($patent['patentNumber']))
                                    <span class="patent-number badge badge-warning">
                                        <i class="fa fa-hashtag"></i> {{ $patent['patentNumber'] }}
                                    </span>
                                    @endif
                                </div>
                                <div class="publication-details">
                                    @if(!empty($patent['nameOfTheInventors']))
                                    <p class="inventors">
                                        <strong>Inventors:</strong> {{ $patent['nameOfTheInventors'] }}
                                    </p>
                                    @endif
                                    @if(!empty($patent['publishedDate']))
                                    <p class="published-date">
                                        <strong>Published Date:</strong> {{ $patent['publishedDate'] }}
                                    </p>
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

        <!-- Book Chapters -->
        @php
            $bookChapters = is_array($bookChapter ?? null) ? $bookChapter : [];
            if (is_array($bookChapters) && count($bookChapters) > 0) {
                usort($bookChapters, function($a, $b) {
                    $yearA = isset($a['year']) && !empty($a['year']) ? (string)$a['year'] : '0000';
                    $yearB = isset($b['year']) && !empty($b['year']) ? (string)$b['year'] : '0000';
                    preg_match('/(\d{4})/', $yearA, $matchA);
                    preg_match('/(\d{4})/', $yearB, $matchB);
                    $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
                    $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;
                    return $numB - $numA;
                });
            }
        @endphp
        @if(is_array($bookChapters) && count($bookChapters) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="publications-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-book text-primary"></i> Book Chapters
                    </h2>
                    <div class="publications-timeline">
                        @foreach($bookChapters as $yearData)
                            @if(is_array($yearData) && isset($yearData['year']))
                            <div class="year-section mb-4">
                                <h3 class="year-title">
                                    <i class="fa fa-calendar text-info"></i> {{ $yearData['year'] }}
                                </h3>
                                @if(isset($yearData['bookChapters']) && is_array($yearData['bookChapters']))
                                <ul class="pub-arrow-list">
                                    @foreach($yearData['bookChapters'] as $chapter)
                                    <li>
                                        <div class="pub-arrow-text">
                                            {{ $chapter['bookName'] ?? 'Book Chapter' }}
                                            @if(!empty($chapter['authors']))
                                            <span class="pub-meta">Authors: {{ $chapter['authors'] }}</span>
                                            @endif
                                            @if(!empty($chapter['link']))
                                            <a href="{{ $chapter['link'] }}" target="_blank" rel="noopener" class="pub-link">View Chapter</a>
                                            @endif
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Journal Publications -->
        @php
            $journalPublications = is_array($journalPublication ?? null) ? $journalPublication : [];
            if (is_array($journalPublications) && count($journalPublications) > 0) {
                usort($journalPublications, function($a, $b) {
                    $yearA = isset($a['year']) && !empty($a['year']) ? (string)$a['year'] : '0000';
                    $yearB = isset($b['year']) && !empty($b['year']) ? (string)$b['year'] : '0000';
                    preg_match('/(\d{4})/', $yearA, $matchA);
                    preg_match('/(\d{4})/', $yearB, $matchB);
                    $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
                    $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;
                    return $numB - $numA;
                });
            }
        @endphp
        @if(is_array($journalPublications) && count($journalPublications) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="publications-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-newspaper-o text-success"></i> Journal Publications
                    </h2>
                    <div class="publications-timeline">
                        @foreach($journalPublications as $yearData)
                            @if(is_array($yearData) && isset($yearData['year']))
                            <div class="year-section mb-4">
                                <h3 class="year-title">
                                    <i class="fa fa-calendar text-info"></i> {{ $yearData['year'] }}
                                </h3>
                                @if(isset($yearData['publications']) && is_array($yearData['publications']))
                                <ul class="pub-arrow-list">
                                    @foreach($yearData['publications'] as $publication)
                                    <li>
                                        <div class="pub-arrow-text">
                                            {{ $publication['journalName'] ?? $publication['title'] ?? 'Journal Publication' }}
                                            @if(!empty($publication['journal']))
                                            <span class="pub-meta">Journal: {{ $publication['journal'] }}</span>
                                            @endif
                                            @if(!empty($publication['authors']))
                                            <span class="pub-meta">Authors: {{ $publication['authors'] }}</span>
                                            @endif
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Conference List -->
        @php
            $conferences = is_array($conferenceList ?? null) ? $conferenceList : [];
        @endphp
        @if(is_array($conferences) && count($conferences) > 0)
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="publications-section">
                    <h2 class="section-title text-center mb-4">
                        <i class="fa fa-users text-info"></i> Conference Publications
                    </h2>
                    <ul class="pub-arrow-list">
                        @foreach($conferences as $index => $conference)
                        <li>
                            <div class="pub-arrow-text">
                                {{ $conference['list'] ?? $conference['title'] ?? 'Conference ' . ($index + 1) }}
                                @if(!empty($conference['description']))
                                <span class="pub-meta">{!! $conference['description'] !!}</span>
                                @endif
                                @if(!empty($conference['date']))
                                <span class="pub-meta">Date: {{ $conference['date'] }}</span>
                                @endif
                                @if(!empty($conference['location']))
                                <span class="pub-meta">Location: {{ $conference['location'] }}</span>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- No Data Message -->
        @if(empty($patents) && empty($bookChapters) && empty($journalPublications) && empty($conferences))
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <i class="fa fa-file-text fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Publications Available</h3>
                    <p class="text-muted">Publication information will be updated soon.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
.publications-section .year-title {
    color: #125875;
    font-size: 20px !important;
    font-weight: 700;
    margin: 0 0 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e6eef2;
}
.pub-arrow-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.pub-arrow-list > li {
    position: relative;
    margin: 0 0 12px;
    padding: 14px 16px 14px 36px;
    background: #f7fafc;
    border: 1px solid #e4edf2;
    border-radius: 8px;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}
.pub-arrow-list > li:hover {
    background: #fff;
    border-color: #c9dde8;
    box-shadow: 0 6px 18px rgba(18, 88, 117, 0.08);
}
.pub-arrow-list > li::before {
    content: "→";
    position: absolute;
    left: 12px;
    top: 14px;
    color: #125875;
    font-weight: 700;
    font-size: 18px !important;
    line-height: 1.5;
}
.pub-arrow-text {
    color: #334155;
    font-size: 16px !important;
    line-height: 1.65;
}
.pub-arrow-text .pub-meta {
    display: block;
    margin-top: 6px;
    color: #64748b;
    font-size: 14px !important;
}
.pub-arrow-text .pub-link {
    display: inline-block;
    margin-top: 8px;
    color: #125875;
    font-weight: 600;
    font-size: 15px !important;
    text-decoration: none;
}
.pub-arrow-text .pub-link:hover {
    text-decoration: underline;
}
</style>
@endpush