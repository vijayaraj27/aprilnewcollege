<?php

namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\Web\Department;
use App\Models\Language;
use App\Models\Web\AchievementItem;
use App\Models\Web\ActivityItem;
use App\Models\Web\CourseItem;
use App\Models\Web\EventItem;
use App\Models\Web\FacultyMember;
use App\Models\Web\NotificationBoard;
use App\Models\Web\UpcomingEvent;
use App\Models\Web\InfrastructureItem;
use App\Models\Web\LibraryItem;
use App\Models\Web\MagazineItem;
use App\Models\Web\NewsletterItem;
use App\Models\Web\PlacementItem;
use App\Models\Web\ProgrammeOutcome;
use App\Models\Web\PublicationItem;
use App\Models\Web\ResearchItem;
use App\Models\Web\Slider;
use App\Models\Web\SyllabusItem;
use App\Models\Web\Testimonial;
use App\Models\Web\VideoItem;

use DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     ** @param  string  $slug
     * @param  string  $subpage
     * 
     */
    public function index()
    {
       try {
            $data['departments'] =Department::where('status', 1)->get();
            //  dd($departments);
            //  $treeView = $this->buildTreeView($departments);
            //  dd($data);  //exit;
            return view('web.department', ['departments' => $data]);
        } catch (\Exception $e) {
            // Log or display the error message
           dd($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function show($slug)
    // {
    //     try {
    //         // Get the department details
    //         $department = Department::where('slug', $slug)
    //                                 ->where('status', '1')
    //                                 ->firstOrFail();
    //         // Get sliders related to the department
    //         $sliders = Slider::where('language_id', Language::version()->id)
    //                          ->where('department_id', $department->id) // Filter by department ID
    //                          ->where('status', '1')
    //                          ->orderBy('id', 'asc')
    //                          ->get();
    //         // Pass department and sliders data to the view
    //         $data = [
    //             'department' => $department,
    //             'sliders' => $sliders
    //         ];
    //         return view('web.department-single', $data);
    //     } catch (\Exception $e) {
    //         // Log or display the error message
    //         dd($e->getMessage());
    //     }
    // }

    public function show($departmentSlug, $section = 'about')
    {
        // Fetch department by slug (with common menu/legacy aliases)
        $department = $this->resolveDepartmentBySlug($departmentSlug);
        if (!$department) {
            abort(404);
        }

        // If an alias was used, redirect to the canonical slug URL.
        if ($department->slug !== $departmentSlug) {
            $target = $section && $section !== 'about'
                ? url('/department/'.$department->slug.'/'.$section)
                : url('/department/'.$department->slug);

            return redirect($target, 301);
        }

        // Section to table mapping
        $tableMapping = [
            'about'           => 'department_about_us',
            'achievements'    => 'department_achievements',
            'activities'      => 'department_activities',
            'courses'         => 'department_courses',
            'events'          => 'department_events',
            'faculties'       => 'department_faculties',
            'infrastructures' => 'department_infrastructures',
            'libraries'       => 'department_libraries',
            'magazines'       => 'department_magazines',
            'newsletters'     => 'department_newsletters',
            'placements'      => 'department_placements',
            'publications'    => 'department_publications',
            'research'        => 'department_research',
            'syllabus'        => 'department_syllabus',
            'videos'          => 'department_videos'
        ];

        // Validate section
        if (!array_key_exists($section, $tableMapping)) {
            abort(404, 'Invalid section.');
        }

        // Fetch table data
        $tableName = $tableMapping[$section];
        $data = DB::table($tableName)
            ->where('departmentId', $department->id)
            ->first();

        // Department home/about uses that department's own sliders only.
        $sliders = Slider::where('language_id', Language::version()->id)
                         ->where('status', '1')
                         ->where('department_id', $department->id)
                         ->orderBy('id', 'asc')
                         ->get();
        // Department pages show only that department's testimonials (not home).
        $testimonials = Testimonial::where('language_id', Language::version()->id)
            ->where('status', '1')
            ->where('department_id', $department->id)
            ->orderBy('id', 'asc')
            ->get();

        // Safe extraction helper
        $getValue = fn($obj, $key, $default = null) =>
            (is_object($obj) && property_exists($obj, $key) && !empty($obj->$key)) ? $obj->$key : $default;

        // Prepare response
        $response = [
            'data'                         => $data,
            'section'                      => $section,
            'sliders'                      => $sliders,
            'testimonials'                 => $testimonials,
            'departmentSectionImage'       => json_decode($getValue($data, 'departmentSectionImage', '[]'), true),
            'sectionAbout'                 => json_decode($getValue($data, 'sectionAbout', '[]'), true),
            'vision'                       => $getValue($data, 'vision', ''),
            'mission'                      => $getValue($data, 'mission', ''),
            'coreValue'                    => $getValue($data, 'coreValue', ''),
            'programmeEducationalObjectives' => [],
            'programmeOutcomes'            => [],
            'programmeSpecificOutcomes'    => [],
            'contact'                      => json_decode($getValue($data, 'contact', '[]'), true),
            'department'                   => $department,
        ];

        // One indexed query for PEO / PO / PSO (shared CRUD table)
        $programmeOutcomesByType = ProgrammeOutcome::where('department_id', $department->id)
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('type');

        $mapOutcomeItem = function ($row) {
            if (!empty($row->title)) {
                return [
                    'title' => $row->title,
                    'description' => $row->description,
                ];
            }

            return $row->description;
        };

        $response['programmeEducationalObjectives'] = ($programmeOutcomesByType->get('peo') ?? collect())
            ->map($mapOutcomeItem)
            ->values()
            ->all();
        $response['programmeOutcomes'] = ($programmeOutcomesByType->get('po') ?? collect())
            ->map($mapOutcomeItem)
            ->values()
            ->all();
        $response['programmeSpecificOutcomes'] = ($programmeOutcomesByType->get('pso') ?? collect())
            ->map($mapOutcomeItem)
            ->values()
            ->all();

        // Department upcoming events & notifications (department home / about page)
        if ($section === 'about') {
            $response['upcomingEvents'] = UpcomingEvent::forDepartment($department->id)
                ->active()
                ->ordered()
                ->limit(10)
                ->get();
            $response['notifications'] = NotificationBoard::forDepartment($department->id)
                ->active()
                ->ordered()
                ->limit(10)
                ->get();
        }

        // Add section-specific data based on the current section
        if ($data) {
            switch ($section) {
                case 'achievements':
                    // lists loaded below from AchievementItem CRUD
                    break;
                    
                case 'activities':
                    // lists loaded below from ActivityItem CRUD
                    break;
                    
                case 'events':
                    // lists loaded below from EventItem CRUD
                    break;
                    
                case 'infrastructures':
                    // buildings loaded below from CRUD table
                    break;
                    
                case 'libraries':
                    // lists loaded below from LibraryItem CRUD
                    break;
                    
                case 'magazines':
                    // lists loaded below from MagazineItem CRUD
                    break;
                    
                case 'newsletters':
                    // lists loaded below from NewsletterItem CRUD
                    break;
                    
                case 'placements':
                    // lists loaded below from PlacementItem CRUD
                    break;
                    
                case 'publications':
                    // lists loaded below from PublicationItem CRUD
                    break;
                    
                case 'research':
                    // lists loaded below from ResearchItem CRUD
                    break;
                    
                case 'syllabus':
                    // lists loaded below from SyllabusItem CRUD
                    break;
                    
                case 'videos':
                    // lists loaded below from VideoItem CRUD
                    break;
                    
                case 'courses':
                    // lists loaded below from CourseItem CRUD
                    break;
            }
        }

        if ($section === 'faculties') {
            $facultyMembers = FacultyMember::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');
            $response['teachingStaff'] = $facultyMembers->get('teaching') ?? collect();
            $response['nonTeachingStaff'] = $facultyMembers->get('non-teaching') ?? collect();
        }

        if ($section === 'infrastructures') {
            $response['buildings'] = InfrastructureItem::where('department_id', $department->id)
                ->where('type', 'buildings')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        if ($section === 'achievements') {
            $achievementItems = AchievementItem::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');

            $response['staffAchievements'] = $achievementItems->get('staff') ?? collect();
            $response['studentAchievements'] = $this->groupAchievementItemsByYear(
                $achievementItems->get('student') ?? collect(),
                'achievements'
            );
            $response['studentAchievementsTableFormat'] = $this->groupAchievementItemsByYear(
                $achievementItems->get('student-table') ?? collect(),
                'achievements'
            );
            $response['studentAchievementsAppeciationList'] = $this->groupAchievementItemsByYear(
                $achievementItems->get('student-appreciation') ?? collect(),
                'appreciations'
            );
        }

        if ($section === 'placements') {
            $placementItems = PlacementItem::where('department_id', $department->id)
                ->where('type', 'student-placed')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            $response['studentPlaced'] = $this->groupContentItemsByYear($placementItems, 'placements', function ($item) {
                return [
                    'studentRegNumber' => $item->metaGet('studentRegNumber'),
                    'studentName' => $item->metaGet('studentName', $item->title),
                    'companyName' => $item->metaGet('companyName'),
                ];
            }, PlacementItem::class);
        }

        if ($section === 'videos') {
            $response['videos'] = VideoItem::where('department_id', $department->id)
                ->where('type', 'videos')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'title' => $item->title,
                        'YoutubeLink' => $item->metaGet('YoutubeLink'),
                        'description' => $item->description,
                    ];
                })
                ->values()
                ->all();
        }

        if ($section === 'courses') {
            $response['courses'] = CourseItem::where('department_id', $department->id)
                ->where('type', 'courses')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'title' => $item->title,
                        'pdfLink' => $item->metaGet('pdfLink'),
                    ];
                })
                ->values()
                ->all();
        }

        if ($section === 'newsletters') {
            $newsletterItems = NewsletterItem::where('department_id', $department->id)
                ->where('type', 'newsletters')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->sortByDesc(fn ($item) => NewsletterItem::yearSortKey($item->year))
                ->values();

            $response['newsletter'] = $newsletterItems->map(function ($item) {
                return [
                    'year' => $item->year,
                    'pdfLink' => $item->metaGet('pdfLink'),
                    'description' => $item->description,
                ];
            })->all();
        }

        if ($section === 'events') {
            $eventItems = EventItem::where('department_id', $department->id)
                ->where('type', 'events')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $response['eventDetails'] = $eventItems->map(function ($item) {
                return [
                    'title' => $item->title,
                    'description' => $item->description,
                    'image1' => $item->metaGet('image1'),
                    'image2' => $item->metaGet('image2'),
                    // Legacy key used by older migrated rows / templates
                    'imageFile' => $item->metaGet('image1'),
                ];
            })->values()->all();
        }

        if ($section === 'magazines') {
            $magazineItems = MagazineItem::where('department_id', $department->id)
                ->where('type', 'magazines')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->sortByDesc(fn ($item) => MagazineItem::yearSortKey($item->year))
                ->values();

            $response['magazines'] = $magazineItems->map(function ($item) {
                return [
                    'year' => $item->year,
                    'pdfLink' => $item->metaGet('pdfLink'),
                    'description' => $item->description,
                ];
            })->all();
        }

        if ($section === 'syllabus') {
            $syllabusItems = SyllabusItem::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');

            $mapSyllabus = function ($item) {
                $row = [
                    'Title' => $item->title,
                    'programType' => $item->metaGet('programType'),
                    'pdfLink' => $item->metaGet('pdfLink'),
                ];
                if ($item->type === 'regulation') {
                    $row['regulationType'] = $item->metaGet('regulationType');
                }
                return $row;
            };

            $response['syllabus'] = SyllabusItem::sortForListing($syllabusItems->get('syllabus') ?? collect())
                ->map($mapSyllabus)->values()->all();
            $response['regulation'] = SyllabusItem::sortForListing($syllabusItems->get('regulation') ?? collect())
                ->map($mapSyllabus)->values()->all();
            $response['questionBank'] = SyllabusItem::sortForListing($syllabusItems->get('question-bank') ?? collect())
                ->map($mapSyllabus)->values()->all();
        }

        if ($section === 'libraries') {
            $response['record'] = LibraryItem::where('department_id', $department->id)
                ->where('type', 'record')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'title' => $item->title,
                        'noOfBooks' => $item->metaGet('noOfBooks'),
                        'description' => $item->description,
                    ];
                })
                ->values()
                ->all();
        }

        if ($section === 'research') {
            $researchItems = ResearchItem::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');

            $mapMeta = function ($items) {
                return ($items ?? collect())->map(function ($item) {
                    return array_merge(['title' => $item->title], $item->meta);
                })->values()->all();
            };

            $response['phdHoldersList'] = $mapMeta($researchItems->get('phd'));
            $response['annaUniversityRecognizedSuperviorsNameList'] = $mapMeta($researchItems->get('anna-university'));
            $response['listOfCandidatesPursuingPhdUnderDepartmentSupervisors'] = $mapMeta($researchItems->get('pursuing-phd'));
            $response['listOfDepartmentFacultiesPursuingPhd'] = $mapMeta($researchItems->get('faculties-pursuing-phd'));
            $response['phdAwardedUnderDepartmentSupervisor'] = $mapMeta($researchItems->get('phd-awarded'));
            $response['supervisor'] = $mapMeta($researchItems->get('supervisor'));
            $response['valueAddedGroup'] = $mapMeta($researchItems->get('value-added-group'));
            $response['funds'] = $this->groupContentItemsByYear(
                $researchItems->get('funds') ?? collect(),
                'fundDetails',
                function ($item) {
                    return [
                        'nameOfPi' => $item->metaGet('nameOfPi'),
                        'titleOfTheProject' => $item->metaGet('titleOfTheProject'),
                        'amount' => $item->metaGet('amount'),
                        'fundingAgency' => $item->metaGet('fundingAgency'),
                        'OrderNoAndDate' => $item->metaGet('OrderNoAndDate'),
                        'status' => $item->metaGet('status'),
                    ];
                },
                ResearchItem::class
            );
        }

        if ($section === 'publications') {
            $publicationItems = PublicationItem::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');

            $response['patent'] = ($publicationItems->get('patent') ?? collect())->map(function ($item) {
                return [
                    'NameOfTheInventors' => $item->metaGet('NameOfTheInventors'),
                    'nameOfTheInventors' => $item->metaGet('NameOfTheInventors'),
                    'titleOfThePatents' => $item->metaGet('titleOfThePatents', $item->title),
                    'patentNumber' => $item->metaGet('patentNumber'),
                    'publishedDate' => $item->metaGet('publishedDate'),
                ];
            })->values()->all();

            $response['bookChapter'] = $this->groupContentItemsByYear(
                $publicationItems->get('book-chapter') ?? collect(),
                'bookChapters',
                fn ($item) => ['bookName' => $item->metaGet('bookName', $item->title)],
                PublicationItem::class
            );

            $response['journalPublication'] = $this->groupContentItemsByYear(
                $publicationItems->get('journal') ?? collect(),
                'publications',
                function ($item) {
                    return [
                        'journalName' => $item->metaGet('journalName', $item->title),
                        'title' => $item->metaGet('journalName', $item->title),
                        'journal' => '',
                        'authors' => '',
                    ];
                },
                PublicationItem::class
            );

            $response['conferenceList'] = ($publicationItems->get('conference') ?? collect())->map(function ($item) {
                return ['list' => $item->metaGet('list', $item->title)];
            })->values()->all();
        }

        if ($section === 'activities') {
            $activityItems = ActivityItem::where('department_id', $department->id)
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('type');

            $response['departmentActivity'] = $this->groupContentItemsByYear(
                $activityItems->get('department-activity') ?? collect(),
                'activities',
                function ($item) {
                    return [
                        'teacherName' => $item->metaGet('teacherName'),
                        'programmeTitle' => $item->metaGet('programmeTitle', $item->title),
                        'organizer' => $item->metaGet('organizer'),
                        'duration' => $item->metaGet('duration'),
                    ];
                },
                ActivityItem::class
            );

            $response['studentParticipation'] = $this->groupContentItemsByYear(
                $activityItems->get('student-participation') ?? collect(),
                'participations',
                function ($item) {
                    return [
                        'eventDate' => $item->metaGet('eventDate'),
                        'eventName' => $item->metaGet('eventName', $item->title),
                        'conductedBy' => $item->metaGet('conductedBy'),
                        'nameOfTheStudentsParticipated' => $item->metaGet('nameOfTheStudentsParticipated'),
                    ];
                },
                ActivityItem::class
            );

            $response['interInstituteEventsWinningPrize'] = $this->groupContentItemsByYear(
                $activityItems->get('inter-institute-prize') ?? collect(),
                'events',
                function ($item) {
                    return [
                        'eventDate' => $item->metaGet('eventDate'),
                        'date' => $item->metaGet('eventDate'),
                        'eventName' => $item->metaGet('eventName', $item->title),
                        'conductedBy' => $item->metaGet('conductedBy'),
                        'organizedBy' => $item->metaGet('conductedBy'),
                        'nameOfTheStudentsParticipated' => $item->metaGet('nameOfTheStudentsParticipated'),
                        'studentName' => $item->metaGet('nameOfTheStudentsParticipated'),
                        'prizeWon' => $item->metaGet('prizeWon'),
                        'prize' => $item->metaGet('prizeWon'),
                    ];
                },
                ActivityItem::class
            );

            $response['industrialVisit'] = ($activityItems->get('industrial-visit') ?? collect())->map(function ($item) {
                return [
                    'nameOftheIndustry' => $item->metaGet('nameOftheIndustry', $item->title),
                    'semester' => $item->metaGet('semester'),
                    'staffAccompanied' => $item->metaGet('staffAccompanied'),
                    'Duration' => $item->metaGet('Duration'),
                ];
            })->values()->all();
        }

        // Choose view based on section
        $viewPath = match ($section) {
            'about'           => 'web.department.about',
            'achievements'    => 'web.department.achievements',
            'activities'      => 'web.department.activities',
            'courses'         => 'web.department.courses',
            'events'          => 'web.department.events',
            'faculties'       => 'web.department.faculties',
            'infrastructures' => 'web.department.infrastructures',
            'libraries'       => 'web.department.libraries',
            'magazines'       => 'web.department.magazines',
            'newsletters'     => 'web.department.newsletters',
            'placements'      => 'web.department.placements',
            'publications'    => 'web.department.publications',
            'research'        => 'web.department.research',
            'syllabus'        => 'web.department.syllabus',
            'videos'          => 'web.department.videos',
            default           => 'web.department.about',
        };

        return view($viewPath, $response);
    }
    
    // Common function to handle Add/Update
    public function storeOrUpdate(Request $request, $section, $departmentId)
    {
        // Define the table name dynamically based on section
        $table = 'department_' . $section;

        // Validate if table exists in the schema
        if (!Schema::hasTable($table)) {
            return response()->json(['error' => 'Invalid section provided.'], 400);
        }

        // Validation rules based on table structure
        $rules = $this->getValidationRules($section);

        // Validate input
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Check if record exists for the department
        $record = DB::table($table)->where('departmentId', $departmentId)->first();

        // Prepare data to insert or update
        $data = $request->except('_method', '_token'); // Exclude Laravel's hidden fields
        $data['departmentId'] = $departmentId;
        $data['updatedAt'] = now(); // Add updated timestamp

        if ($record) {
            // Update if the record exists
            DB::table($table)->where('departmentId', $departmentId)->update($data);
            return response()->json(['message' => 'Data updated successfully']);
        } else {
            // Insert new record
            $data['createdAt'] = now(); // Add created timestamp
            DB::table($table)->insert($data);
            return response()->json(['message' => 'Data added successfully']);
        }
    }

    // Function to define validation rules based on section
    protected function getValidationRules($section)
    {
        $rules = [];

        switch ($section) {
            case 'about_us':
                $rules = [
                    'slider' => 'required|json',
                    'sectionAbout' => 'required|json',
                    'vision' => 'required|string',
                    'mission' => 'required|string',
                    'coreValue' => 'required|string',
                    'departmentSectionImage' => 'required|json',
                    'testimonial' => 'nullable|json',
                    'programmeEducationalObjectives' => 'nullable|json',
                    'programmeOutcomes' => 'nullable|json',
                    'programmeSpecificOutcomes' => 'nullable|json',
                    'contact' => 'nullable|json'
                ];
                break;

            case 'faculties':
                $rules = [
                    'facultyName' => 'required|string',
                    'designation' => 'required|string',
                    'experience' => 'required|integer',
                    'departmentId' => 'required|integer',
                    'qualification' => 'required|string',
                    'publication' => 'nullable|json',
                    'awards' => 'nullable|json'
                ];
                break;

            case 'activities':
                $rules = [
                    'title' => 'required|string',
                    'imageFile' => 'required|string',
                    'description' => 'required|string',
                    'departmentActivity' => 'required|json',
                    'studentParticipation' => 'required|json',
                    'interInstituteEventsWinningPrize' => 'nullable|json'
                ];
                break;


            case 'research':
                $rules = [
                    'title' => 'required|string',
                    'imageFile' => 'required|string',
                    'description' => 'required|string',
                    'phdHoldersList' => 'required|json',
                    'annaUniversityRecognizedSuperviorsNameList' => 'required|json',
                    'listOfCandidatesPursuingPhdUnderDepartmentSupervisors' => 'required|json',
                    'listOfDepartmentFacultiesPursuingPhd' => 'required|json',
                    'phdAwardedUnderDepartmentSupervisor' => 'required|json',
                    'supervisor' => 'nullable|json',
                    'funds' => 'nullable|json',
                    'valueAddedGroup' => 'nullable|json'
                ];
                break;

            case 'publications':
                $rules = [
                    'title' => 'required|string',
                    'imageFile' => 'nullable|string',
                    'description' => 'nullable|string',
                    'patent' => 'nullable|json',
                    'bookChapter' => 'nullable|json',
                    'journalPublication' => 'nullable|json',
                    'conferenceList' => 'nullable|json'
                ];
                break;

            case 'infrastructures':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'imageFile' => 'required|string',
                    'facilities' => 'nullable|json'
                ];
                break;

            case 'libraries':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'imageFile' => 'nullable|string',
                    'libraryDetails' => 'required|json'
                ];
                break;

            case 'newsletters':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'imageFile' => 'nullable|string',
                    'newsletterDetails' => 'required|json'
                ];
                break;

            case 'magazines':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'imageFile' => 'nullable|string',
                    'magazineDetails' => 'required|json'
                ];
                break;

            case 'placements':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'placementStatistics' => 'required|json',
                    'imageFile' => 'nullable|string'
                ];
                break;

            case 'events':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'eventDetails' => 'required|json',
                    'imageFile' => 'nullable|string'
                ];
                break;

            case 'syllabus':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'syllabusDetails' => 'required|json',
                    'imageFile' => 'nullable|string'
                ];
                break;

            case 'videos':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'videoUrl' => 'required|string',
                    'imageFile' => 'nullable|string'
                ];
                break;

            case 'achievements':
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                    'videoUrl' => 'required|string',
                    'imageFile' => 'nullable|string',
                    'staffAchievements' => 'nullable|json',
                    'studentAchievements' => 'nullable|json',
                    'studentAchievementsTableFormat' => 'nullable|json',
                    'studentAchievementsAppeciationList' => 'nullable|json'
                ];
                break;

            // Add cases for other sections here

            default:
                $rules = [
                    'title' => 'required|string',
                    'description' => 'required|string',
                ];
                break;
        }

        return $rules;
    }

    public function showFaculty($slug, $subpage="faculty")
    {
        try {
            $data['sliders'] = Slider::where('language_id', Language::version()->id)
            ->where('status', '1')
            ->orderBy('id', 'asc')
            ->get();
           // echo "test"; exit;
            // Retrieve the department
            $data['department'] = Department::where('slug', $slug)
                ->where('status', '1')
                ->firstOrFail();
                //echo "test"; print_r($data);  exit;
            // Retrieve additional data for the subpage if needed
            // For example, you might have a different model for subpages
            // Pass data to the view
            return view('web.department-faculty', $data);
        } catch (\Exception $e) {
            // Log or display the error message
            dd($e->getMessage());
        }
    }

    private function groupAchievementItemsByYear($items, string $childrenKey): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $year = trim((string) ($item->year ?? ''));
            // Skip empty / "Other" year buckets on the public page
            if ($year === '' || strcasecmp($year, 'Other') === 0) {
                continue;
            }
            if (!isset($grouped[$year])) {
                $grouped[$year] = [
                    'year' => $year,
                    $childrenKey => [],
                    '_sort' => AchievementItem::yearSortKey($year),
                ];
            }

            if ($childrenKey === 'appreciations') {
                $grouped[$year][$childrenKey][] = [
                    'studentName' => $item->student_name,
                    'yearOfStudent' => $item->year_of_student,
                    'eventName' => $item->event_name,
                    'projectName' => $item->project_name,
                    'supervisor' => $item->supervisor,
                    'status' => $item->result_status,
                    'achievement' => $item->description,
                ];
            } elseif ($item->type === 'student-table') {
                $grouped[$year][$childrenKey][] = [
                    'studentName' => $item->student_name,
                    'eventNature' => $item->event_nature,
                    'eventName' => $item->event_name,
                    'periodDate' => $item->period_date,
                    'organizedBy' => $item->organized_by,
                    'awards' => $item->awards,
                ];
            } else {
                $grouped[$year][$childrenKey][] = [
                    'title' => $item->title,
                    'description' => $item->description,
                    'image' => $item->image,
                ];
            }
        }

        $grouped = array_values($grouped);
        usort($grouped, function ($a, $b) {
            return ($b['_sort'] ?? 0) <=> ($a['_sort'] ?? 0);
        });

        foreach ($grouped as &$group) {
            unset($group['_sort']);
        }
        unset($group);

        return $grouped;
    }

    private function groupContentItemsByYear($items, string $childrenKey, callable $mapChild, string $modelClass): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $year = trim((string) ($item->year ?? ''));
            if ($year === '' || strcasecmp($year, 'Other') === 0) {
                continue;
            }
            if (!isset($grouped[$year])) {
                $grouped[$year] = [
                    'year' => $year,
                    $childrenKey => [],
                    '_sort' => $modelClass::yearSortKey($year),
                ];
            }
            $grouped[$year][$childrenKey][] = $mapChild($item);
        }

        $grouped = array_values($grouped);
        usort($grouped, function ($a, $b) {
            return ($b['_sort'] ?? 0) <=> ($a['_sort'] ?? 0);
        });

        foreach ($grouped as &$group) {
            unset($group['_sort']);
        }
        unset($group);

        return $grouped;
    }

    /**
     * Resolve a department by canonical slug or known menu/legacy aliases.
     */
    private function resolveDepartmentBySlug(string $slug): ?Department
    {
        $department = Department::where('slug', $slug)->first();
        if ($department) {
            return $department;
        }

        $aliases = [
            'bio-medical' => 'biomedical-engineering',
            'bio-medical-ug' => 'biomedical-engineering',
            'biomedical' => 'biomedical-engineering',
            'bio-technology' => 'biotechnology',
            'bio-tech' => 'biotechnology',
            'master-business-administration' => 'master-of-business-administration',
            'mba' => 'master-of-business-administration',
        ];

        $canonical = $aliases[$slug] ?? null;
        if (!$canonical) {
            return null;
        }

        return Department::where('slug', $canonical)->first();
    }
}
