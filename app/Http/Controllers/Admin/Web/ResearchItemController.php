<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesDepartmentContentItems;
use App\Http\Controllers\Controller;
use App\Models\Web\ResearchItem;

class ResearchItemController extends Controller
{
    use ManagesDepartmentContentItems;

    public function __construct()
    {
        $this->title  = 'Research Item';
        $this->route  = 'admin.research-item';
        $this->view   = 'admin.web.content-item';
        $this->path   = 'research-items';
        $this->access = 'research-item';

        $this->middleware('permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }

    protected function modelClass(): string
    {
        return ResearchItem::class;
    }

    protected function formFields(string $type): array
    {
        return match ($type) {
            'phd' => [
                ['name' => 'nameOfThePhdHolder', 'label' => 'Name of the PhD Holder', 'type' => 'text', 'required' => true],
                ['name' => 'facultyUnderWhichPhdAwarded', 'label' => 'Faculty Under Which PhD Awarded', 'type' => 'text'],
                ['name' => 'areaOfResearch', 'label' => 'Area of Research', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'yearOfAward', 'label' => 'Year of Award', 'type' => 'text'],
            ],
            'anna-university' => [
                ['name' => 'nameOfTheSupervior', 'label' => 'Name of the Supervisor', 'type' => 'text', 'required' => true],
                ['name' => 'areaOfResearch', 'label' => 'Area of Research', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'AnnaUniversityRecognitionNo', 'label' => 'Anna University Recognition No', 'type' => 'text'],
                ['name' => 'noofCandidatesUnderDeptResearchCentre', 'label' => 'Candidates Under Dept Research Centre', 'type' => 'text'],
                ['name' => 'noofCandidatesUnderOtherUniversity', 'label' => 'Candidates Under Other University', 'type' => 'text'],
                ['name' => 'noofCandidatesPursuingPhd', 'label' => 'Candidates Pursuing PhD', 'type' => 'text'],
            ],
            'pursuing-phd' => [
                ['name' => 'nameOfTheCandidate', 'label' => 'Name of the Candidate', 'type' => 'text', 'required' => true],
                ['name' => 'yearOfRegistration', 'label' => 'Year of Registration', 'type' => 'text'],
                ['name' => 'supervisorName', 'label' => 'Supervisor Name', 'type' => 'text'],
                ['name' => 'areaOfResearch', 'label' => 'Area of Research', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
            ],
            'faculties-pursuing-phd' => [
                ['name' => 'nameOfTheCandidate', 'label' => 'Name of the Candidate', 'type' => 'text', 'required' => true],
                ['name' => 'yearOfRegistration', 'label' => 'Year of Registration', 'type' => 'text'],
                ['name' => 'regNo', 'label' => 'Registration No', 'type' => 'text'],
                ['name' => 'supervisorName', 'label' => 'Supervisor Name', 'type' => 'text'],
                ['name' => 'universityRegistered', 'label' => 'University Registered', 'type' => 'text'],
                ['name' => 'areaOfResearch', 'label' => 'Area of Research', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
            ],
            'phd-awarded' => [
                ['name' => 'NameofTheResearchScholar', 'label' => 'Name of the Research Scholar', 'type' => 'text', 'required' => true],
                ['name' => 'regNo', 'label' => 'Registration No', 'type' => 'text'],
                ['name' => 'university', 'label' => 'University', 'type' => 'text'],
                ['name' => 'supervisorName', 'label' => 'Supervisor Name', 'type' => 'text'],
                ['name' => 'titleOfTheThesis', 'label' => 'Title of the Thesis', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'vivaVoceDate', 'label' => 'Viva Voce Date', 'type' => 'text'],
            ],
            'supervisor' => [
                ['name' => 'Name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'profileLink', 'label' => 'Profile Link', 'type' => 'text', 'wide' => true],
            ],
            'funds' => [
                ['name' => 'nameOfPi', 'label' => 'Name of PI', 'type' => 'text', 'required' => true],
                ['name' => 'titleOfTheProject', 'label' => 'Title of the Project', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'text'],
                ['name' => 'fundingAgency', 'label' => 'Funding Agency', 'type' => 'text'],
                ['name' => 'OrderNoAndDate', 'label' => 'Order No and Date', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
            ],
            'value-added-group' => [
                ['name' => 'Group', 'label' => 'Group', 'type' => 'text', 'required' => true],
                ['name' => 'faculty', 'label' => 'Faculty', 'type' => 'text'],
                ['name' => 'Activities', 'label' => 'Activities', 'type' => 'textarea', 'rows' => 4],
            ],
            default => [],
        };
    }
}
