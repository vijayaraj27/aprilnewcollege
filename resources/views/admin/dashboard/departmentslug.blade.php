@extends('admin.layouts.master')
@section('title', $title)
@section('content')

<!-- Start Content-->
<div class="main-body">
    <div class="page-wrapper">
        <!-- [ Main Content ] start -->
        <div class="row">

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Department Section </h5>
                    </div>
                    <div class="card-block">

                        <h4>{{$department->title}} </h4>
                        <!-- [ Data table ] start -->
                        <div class="table-responsive">


                            <div class="text-column col-lg-12 col-md-12 col-sm-12 my-5">

                                <ul class="d-flex  flex-column listmargin clearfix list-unstyled">

                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/about-us/{{$department->id}}"
                                                target="_blank">About</a> </strong>
                    <ul>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/slider?department_id={{$department->id}}" target="_blank">Slider</a>
                            </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/testimonial?department_id={{$department->id}}"
                                    target="_blank">Testimonial</a> </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/upcoming-event?department_id={{$department->id}}"
                                    target="_blank">Upcoming Events</a> </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/notification-board?department_id={{$department->id}}"
                                    target="_blank">Notifications</a> </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/programme-outcome?department_id={{$department->id}}&type=peo"
                                    target="_blank">Programme Educational Objectives</a> </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/programme-outcome?department_id={{$department->id}}&type=po"
                                    target="_blank">Programme Outcomes</a> </strong></li>
                        <li><strong><a class="btn btn-primary m-2"
                                    href="{{$baseurl}}admin/web/programme-outcome?department_id={{$department->id}}&type=pso"
                                    target="_blank">Programme Specific Outcomes</a> </strong></li>
                    </ul>
                                    </li>

                                    <li>
                                        <a class="btn btn-primary m-2"
                                            href="{{$baseurl}}admin/web/faculties/{{$department->id}}_basic"
                                            target="_blank"> <strong>Faculty</strong> </a>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/faculty-member?department_id={{$department->id}}&type=teaching"
                                                        target="_blank">Teaching Staff</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/faculty-member?department_id={{$department->id}}&type=non-teaching"
                                                        target="_blank">Non Teaching Staff</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/infrastructures/{{$department->id}}_basic"
                                                target="_blank">Infrastructure</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/infrastructure-item?department_id={{$department->id}}&type=buildings"
                                                        target="_blank">Buildings</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/achievements/{{$department->id}}_basic"
                                                target="_blank">Achievements</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/achievement-item?department_id={{$department->id}}&type=staff"
                                                        target="_blank">Staff Achievements</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/achievement-item?department_id={{$department->id}}&type=student"
                                                        target="_blank">Student Achievements</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/achievement-item?department_id={{$department->id}}&type=student-table"
                                                        target="_blank">Student Achievements Table </a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/achievement-item?department_id={{$department->id}}&type=student-appreciation"
                                                        target="_blank">Student Achievements Appreciation</a> </strong>
                                            </li>

                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/placements/{{$department->id}}_basic"
                                                target="_blank">Placement</a></strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/placement-item?department_id={{$department->id}}&type=student-placed"
                                                        target="_blank">Student Placed</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/videomaterials/{{$department->id}}_basic"
                                                target="_blank">Video Materials</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/video-item?department_id={{$department->id}}&type=videos"
                                                        target="_blank">Video link</a> </strong></li>
                                        </ul>
                                    </li>

                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/coursematerials/{{$department->id}}_basic"
                                                target="_blank">Course Materials</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/course-item?department_id={{$department->id}}&type=courses"
                                                        target="_blank">Course link</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/events/{{$department->id}}_basic"
                                                target="_blank">Events</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/event-item?department_id={{$department->id}}&type=events"
                                                        target="_blank">Event List</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/newsletters/{{$department->id}}_basic"
                                                target="_blank">Newsletter</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/newsletter-item?department_id={{$department->id}}&type=newsletters"
                                                        target="_blank">Newsletter link</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/magazines/{{$department->id}}_basic"
                                                target="_blank">Magazines</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/magazine-item?department_id={{$department->id}}&type=magazines"
                                                        target="_blank">Magazines link</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/syllabus/{{$department->id}}_basic"
                                                target="_blank">Syllabus</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/syllabus-item?department_id={{$department->id}}&type=syllabus"
                                                        target="_blank">Syllabus List</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/syllabus-item?department_id={{$department->id}}&type=regulation"
                                                        target="_blank">Regulation</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/syllabus-item?department_id={{$department->id}}&type=question-bank"
                                                        target="_blank">question
                                                        Bank</a> </strong></li>

                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/library/{{$department->id}}_basic"
                                                target="_blank">Department Library</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/library/{{$department->id}}_sectionAbout"
                                                        target="_blank">Section
                                                        About</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/library-item?department_id={{$department->id}}&type=record"
                                                        target="_blank">Department Library Records</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/research/{{$department->id}}_basic"
                                                target="_blank">Research</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=phd"
                                                        target="_blank">phd
                                                        Holders List</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=anna-university"
                                                        target="_blank">anna
                                                        University Recognized Superviors NameList</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=pursuing-phd"
                                                        target="_blank">List Of
                                                        Candidates Pursuing Phd Under Department Supervisors</a>
                                                </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=faculties-pursuing-phd"
                                                        target="_blank">List Of
                                                        Department Faculties Pursuing Phd</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=phd-awarded"
                                                        target="_blank">phd
                                                        Awarded Under Department Supervisor</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=supervisor"
                                                        target="_blank">supervisor</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=funds"
                                                        target="_blank">Funds</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/research-item?department_id={{$department->id}}&type=value-added-group"
                                                        target="_blank">Value
                                                        Added Group</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/publications/{{$department->id}}_basic"
                                                target="_blank">Publications</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/publication-item?department_id={{$department->id}}&type=patent"
                                                        target="_blank">patent</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/publication-item?department_id={{$department->id}}&type=book-chapter"
                                                        target="_blank">book
                                                        Chapter</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/publication-item?department_id={{$department->id}}&type=journal"
                                                        target="_blank">journal
                                                        Publication</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/publication-item?department_id={{$department->id}}&type=conference"
                                                        target="_blank">Conference List</a> </strong></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong><a class="btn btn-primary m-2"
                                                href="{{$baseurl}}admin/web/activities/{{$department->id}}_basic"
                                                target="_blank">Activities</a> </strong>
                                        <ul>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/activity-item?department_id={{$department->id}}&type=department-activity"
                                                        target="_blank">Department Activity</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/activity-item?department_id={{$department->id}}&type=student-participation"
                                                        target="_blank">Student
                                                        Participation</a> </strong></li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/activity-item?department_id={{$department->id}}&type=inter-institute-prize"
                                                        target="_blank">Inter
                                                        Institute Events Winning Prize</a> </strong>
                                            </li>
                                            <li><strong><a class="btn btn-primary m-2"
                                                        href="{{$baseurl}}admin/web/activity-item?department_id={{$department->id}}&type=industrial-visit"
                                                        target="_blank">Industrial
                                                        Visit</a> </strong></li>

                                        </ul>
                                    </li>

                                </ul>



                                <div class="upper-box">
                                    <div class="single-item-carousel owl-carousel owl-theme">
                                        {{--  <figure class="image"><img src="{{ asset('uploads/course/'.$course->attach) }}"
                                        alt="Course"></figure> --}}
                                    </div>
                                </div>

                            </div>

                        </div>
                        <!-- [ Data table ] end -->
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- End Content-->

@endsection