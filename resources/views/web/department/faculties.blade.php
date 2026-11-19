@extends('web.layouts.department')

@section('page-icon', 'fa-users')
@section('page-description', 'Meet Our Faculty Members')

@section('main-content')
<section class="section-padding faculty-page">
    <div class="container-fluid px-0">
        @if(!empty($data->title))
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h1 class="page-title">{{ $data->title }}</h1>
                @if(!empty($data->description))
                <div class="page-description">{!! $data->description !!}</div>
                @endif
            </div>
        </div>
        @endif

        @if(!empty($data->imageFile))
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <img src="{{ asset('uploads/faculties/' . $data->imageFile) }}"
                     alt="Department Faculties"
                     class="img-fluid rounded shadow-lg faculty-banner">
            </div>
        </div>
        @endif

        @php
            $teachingStaff = $teachingStaff ?? collect();
            $nonTeachingStaff = $nonTeachingStaff ?? collect();
            if (!($teachingStaff instanceof \Illuminate\Support\Collection)) {
                $teachingStaff = collect($teachingStaff);
            }
            if (!($nonTeachingStaff instanceof \Illuminate\Support\Collection)) {
                $nonTeachingStaff = collect($nonTeachingStaff);
            }
            $defaultPhoto = asset('uploads/images/default-faculty-avatar.svg');
            $facultyPhotoExists = function ($photo) {
                if (empty($photo)) {
                    return false;
                }
                if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
                    return true;
                }
                return is_file(public_path('uploads/faculty-members/'.$photo))
                    || is_file(base_path('uploads/faculty-members/'.$photo));
            };
        @endphp

        @if($teachingStaff->isNotEmpty())
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="faculties-section">
                    <div class="faculty-section-head text-center mb-4">
                        <h2 class="section-title mb-2">Teaching Staff</h2>
                        <div class="faculty-section-line"></div>
                    </div>
                    <div class="row g-4">
                        @foreach($teachingStaff as $faculty)
                        @php
                            $hasPhoto = $facultyPhotoExists($faculty->photo ?? null);
                            $photoSrc = $hasPhoto ? $faculty->photoUrl() : $defaultPhoto;
                        @endphp
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <article class="faculty-card h-100">
                                <div class="faculty-photo-frame">
                                    <img src="{{ $photoSrc }}"
                                         alt="{{ $faculty->name }}"
                                         class="faculty-photo {{ $hasPhoto ? '' : 'is-placeholder' }}"
                                         loading="lazy">
                                </div>
                                <div class="faculty-body">
                                    <h3 class="faculty-name">{{ $faculty->name }}</h3>
                                    @if(!empty($faculty->designation))
                                    <p class="faculty-designation">{{ $faculty->designation }}</p>
                                    @endif
                                    <ul class="faculty-meta list-unstyled mb-0">
                                        @if(!empty($faculty->email))
                                        <li>
                                            <i class="fa fa-envelope"></i>
                                            <a href="mailto:{{ $faculty->email }}">{{ $faculty->email }}</a>
                                        </li>
                                        @endif
                                        @if(!empty($faculty->au_fin))
                                        <li>
                                            <i class="fa fa-id-card"></i>
                                            <span><strong>AU-FIN:</strong> {{ $faculty->au_fin }}</span>
                                        </li>
                                        @endif
                                        @if(!empty($faculty->aicte_id))
                                        <li>
                                            <i class="fa fa-id-badge"></i>
                                            <span><strong>AICTE ID:</strong> {{ $faculty->aicte_id }}</span>
                                        </li>
                                        @endif
                                    </ul>
                                    @if(!empty($faculty->profile_url))
                                    <div class="faculty-actions">
                                        <a class="btn-view-profile" href="{{ $faculty->profile_url }}" target="_blank" rel="noopener">View Profile</a>
                                    </div>
                                    @endif
                                </div>
                            </article>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($nonTeachingStaff->isNotEmpty())
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="faculties-section">
                    <div class="faculty-section-head text-center mb-4">
                        <h2 class="section-title mb-2">Non-Teaching Staff</h2>
                        <div class="faculty-section-line"></div>
                    </div>
                    <div class="row g-4">
                        @foreach($nonTeachingStaff as $staff)
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <article class="faculty-card h-100">
                                <div class="faculty-photo-frame">
                                    <img src="{{ $defaultPhoto }}"
                                         alt="{{ $staff->name }}"
                                         class="faculty-photo is-placeholder"
                                         loading="lazy">
                                </div>
                                <div class="faculty-body">
                                    <h3 class="faculty-name">{{ $staff->name }}</h3>
                                    @if(!empty($staff->designation))
                                    <p class="faculty-designation">{{ $staff->designation }}</p>
                                    @endif
                                    @if(!empty($staff->qualification))
                                    <p class="faculty-qualification">{{ $staff->qualification }}</p>
                                    @endif
                                </div>
                            </article>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($teachingStaff->isEmpty() && $nonTeachingStaff->isEmpty())
        <div class="row">
            <div class="col-md-12">
                <div class="no-data-message text-center py-5">
                    <img src="{{ $defaultPhoto }}" alt="" class="faculty-photo is-placeholder mb-3" style="width:96px;height:120px;">
                    <h3 class="text-muted">No Faculty Information Available</h3>
                    <p class="text-muted">Faculty information will be updated soon.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
<style>
.faculty-page .faculty-section-head .section-title {
    font-size: 1.85rem;
    font-weight: 700;
    color: #1f2a37;
    letter-spacing: .02em;
}
.faculty-section-line {
    width: 72px;
    height: 3px;
    margin: 0 auto;
    border-radius: 999px;
    background: linear-gradient(90deg, #198754, #20c997);
}
.faculty-card {
    position: relative;
    background: #fff;
    border: 1px solid #e6edf3;
    border-radius: 16px;
    padding: 1.45rem 1.25rem 1.35rem;
    box-shadow: 0 10px 28px rgba(31, 42, 55, .06);
    text-align: center;
    overflow: hidden;
    isolation: isolate;
    transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
}
.faculty-card::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1.5px;
    background: linear-gradient(135deg, rgba(25,135,84,.0), rgba(25,135,84,.0));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    pointer-events: none;
    opacity: 0;
    transition: opacity .28s ease, background .28s ease;
}
.faculty-card::after {
    content: "";
    position: absolute;
    left: 18%;
    right: 18%;
    top: 0;
    height: 3px;
    border-radius: 0 0 8px 8px;
    background: linear-gradient(90deg, #198754, #20c997, #198754);
    background-size: 200% 100%;
    transform: scaleX(.35);
    opacity: 0;
    transition: transform .28s ease, opacity .28s ease;
}
.faculty-card:hover {
    transform: translateY(-8px);
    border-color: rgba(25, 135, 84, .28);
    box-shadow: 0 18px 40px rgba(25, 135, 84, .14), 0 10px 24px rgba(31, 42, 55, .08);
}
.faculty-card:hover::before {
    opacity: 1;
    background: linear-gradient(135deg, rgba(25,135,84,.75), rgba(32,201,151,.55), rgba(25,135,84,.2));
}
.faculty-card:hover::after {
    opacity: 1;
    transform: scaleX(1);
    animation: facultyBorderShine 1.4s linear infinite;
}
@keyframes facultyBorderShine {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}
/* Passport-size frame (approx 35x45mm / 3:4) — no stretch */
.faculty-photo-frame {
    width: 136px;
    height: 170px;
    margin: 0 auto 1.15rem;
    border-radius: 12px;
    overflow: hidden;
    background: #eef3f7;
    border: 1px solid #d7e0e8;
    box-shadow: 0 8px 18px rgba(31, 42, 55, .08);
    transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
}
.faculty-card:hover .faculty-photo-frame {
    transform: translateY(-2px);
    border-color: rgba(25, 135, 84, .35);
    box-shadow: 0 12px 22px rgba(25, 135, 84, .16);
}
.faculty-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    display: block;
}
.faculty-photo.is-placeholder {
    object-fit: cover;
    object-position: center;
    background: #eef3f7;
}
.faculty-name {
    font-size: 1.45rem;
    font-weight: 700;
    color: #1f2a37;
    margin: 0 0 .4rem;
    line-height: 1.35;
}
.faculty-designation {
    color: #198754;
    font-size: 1.18rem;
    font-weight: 600;
    margin: 0 0 1.1rem;
}
.faculty-qualification {
    color: #667788;
    font-size: 1.12rem;
    margin: 0;
}
.faculty-meta {
    text-align: left;
    font-size: 1.28rem;
    color: #445261;
    margin-bottom: 1.1rem !important;
    line-height: 1.6;
}
.faculty-meta li {
    display: flex;
    align-items: flex-start;
    gap: .7rem;
    margin-bottom: .75rem;
    word-break: break-word;
}
.faculty-meta i {
    margin-top: .35rem;
    width: 22px;
    font-size: 1.2rem;
    color: #6b7a8a;
    flex-shrink: 0;
}
.faculty-meta a {
    color: #334155;
    text-decoration: none;
    font-size: 1.28rem;
}
.faculty-meta a:hover {
    color: #198754;
}
.faculty-actions {
    margin-top: 1.1rem;
}
.btn-view-profile {
    display: inline-block;
    padding: .55rem 1.2rem;
    font-size: 1.08rem;
    font-weight: 600;
    line-height: 1.35;
    color: #fff !important;
    background: #198754;
    border: 1px solid #198754;
    border-radius: 8px;
    text-decoration: none !important;
    transition: background .15s ease, border-color .15s ease, transform .15s ease;
}
.btn-view-profile:hover,
.btn-view-profile:focus {
    background: #157347;
    border-color: #146c43;
    color: #fff !important;
    transform: translateY(-1px);
}
@media (max-width: 575.98px) {
    .faculty-photo-frame {
        width: 120px;
        height: 150px;
    }
    .faculty-name {
        font-size: 1.28rem;
    }
    .faculty-designation,
    .btn-view-profile {
        font-size: 1.05rem;
    }
    .faculty-meta,
    .faculty-meta a {
        font-size: 1.15rem;
    }
    .faculty-meta i {
        font-size: 1.1rem;
        width: 20px;
    }
}
</style>
@endsection
