<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('department_research_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('type', 40);
            $table->string('year', 50)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file')->nullable();
            $table->string('image')->nullable();
            $table->json('meta')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->index(['department_id', 'type', 'status', 'year', 'sort_order'], 'res_dept_type_status_year_sort_idx');
        });

        $this->migrateResearch();
        $this->seedPermissions();
    }

    private function migrateResearch(): void
    {
        if (!Schema::hasTable('department_research')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;

        $flatMaps = [
            'phdHoldersList' => [
                'type' => 'phd',
                'titleKey' => 'nameOfThePhdHolder',
                'metaKeys' => ['nameOfThePhdHolder', 'facultyUnderWhichPhdAwarded', 'areaOfResearch', 'yearOfAward'],
            ],
            'annaUniversityRecognizedSuperviorsNameList' => [
                'type' => 'anna-university',
                'titleKey' => 'nameOfTheSupervior',
                'metaKeys' => [
                    'nameOfTheSupervior', 'areaOfResearch', 'AnnaUniversityRecognitionNo',
                    'noofCandidatesUnderDeptResearchCentre', 'noofCandidatesUnderOtherUniversity', 'noofCandidatesPursuingPhd',
                ],
            ],
            'listOfCandidatesPursuingPhdUnderDepartmentSupervisors' => [
                'type' => 'pursuing-phd',
                'titleKey' => 'nameOfTheCandidate',
                'metaKeys' => ['nameOfTheCandidate', 'yearOfRegistration', 'supervisorName', 'areaOfResearch', 'status'],
            ],
            'listOfDepartmentFacultiesPursuingPhd' => [
                'type' => 'faculties-pursuing-phd',
                'titleKey' => 'nameOfTheCandidate',
                'metaKeys' => [
                    'nameOfTheCandidate', 'yearOfRegistration', 'regNo', 'supervisorName',
                    'universityRegistered', 'areaOfResearch', 'status',
                ],
            ],
            'phdAwardedUnderDepartmentSupervisor' => [
                'type' => 'phd-awarded',
                'titleKey' => 'NameofTheResearchScholar',
                'metaKeys' => [
                    'NameofTheResearchScholar', 'regNo', 'university', 'supervisorName',
                    'titleOfTheThesis', 'vivaVoceDate',
                ],
            ],
            'supervisor' => [
                'type' => 'supervisor',
                'titleKey' => 'Name',
                'metaKeys' => ['Name', 'profileLink'],
            ],
            'valueAddedGroup' => [
                'type' => 'value-added-group',
                'titleKey' => 'Group',
                'metaKeys' => ['Group', 'faculty', 'Activities'],
            ],
        ];

        foreach (DB::table('department_research')->get() as $row) {
            $departmentId = $row->departmentId ?? null;

            foreach ($flatMaps as $column => $config) {
                $items = json_decode($row->{$column} ?? '[]', true);
                if (!is_array($items) || empty($items)) {
                    continue;
                }
                $sort = 0;
                foreach (array_values($items) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $title = trim((string) ($item[$config['titleKey']] ?? ''));
                    $meta = [];
                    foreach ($config['metaKeys'] as $key) {
                        if (array_key_exists($key, $item)) {
                            $meta[$key] = is_string($item[$key]) ? trim($item[$key]) : $item[$key];
                        }
                    }
                    if ($title === '' && empty(array_filter($meta, fn ($v) => $v !== null && $v !== ''))) {
                        continue;
                    }
                    $this->insertItem($languageId, $departmentId, $config['type'], null, $title ?: null, $meta, $sort++);
                }
            }

            $funds = json_decode($row->funds ?? '[]', true);
            if (!is_array($funds) || empty($funds)) {
                continue;
            }
            $sort = 0;
            foreach ($funds as $yearData) {
                if (!is_array($yearData)) {
                    continue;
                }
                $year = trim((string) ($yearData['year'] ?? ''));
                if ($year === '' || strcasecmp($year, 'Other') === 0) {
                    continue;
                }
                $details = $yearData['fundDetails'] ?? [];
                if (!is_array($details)) {
                    continue;
                }
                foreach ($details as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $meta = [
                        'nameOfPi' => isset($item['nameOfPi']) ? trim((string) $item['nameOfPi']) : null,
                        'titleOfTheProject' => isset($item['titleOfTheProject']) ? trim((string) $item['titleOfTheProject']) : null,
                        'amount' => isset($item['amount']) ? trim((string) $item['amount']) : null,
                        'fundingAgency' => isset($item['fundingAgency']) ? trim((string) $item['fundingAgency']) : null,
                        'OrderNoAndDate' => isset($item['OrderNoAndDate']) ? trim((string) $item['OrderNoAndDate']) : null,
                        'status' => isset($item['status']) ? trim((string) $item['status']) : null,
                    ];
                    $title = $meta['titleOfTheProject'] ?: ($meta['nameOfPi'] ?: null);
                    if (!$title && empty(array_filter($meta))) {
                        continue;
                    }
                    $this->insertItem($languageId, $departmentId, 'funds', $year, $title, $meta, $sort++);
                }
            }
        }
    }

    private function insertItem(int $languageId, $departmentId, string $type, ?string $year, ?string $title, array $meta, int $sort): void
    {
        DB::table('department_research_items')->insert([
            'language_id'   => $languageId,
            'department_id' => $departmentId,
            'type'          => $type,
            'year'          => $year,
            'title'         => $title,
            'description'   => null,
            'file'          => null,
            'image'         => null,
            'meta'          => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'sort_order'    => $sort,
            'status'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $slug = 'research-item';
        $group = 'Research Item';
        $permissions = [
            ['name' => $slug.'-view', 'group' => $group, 'title' => 'View'],
            ['name' => $slug.'-create', 'group' => $group, 'title' => 'Create'],
            ['name' => $slug.'-edit', 'group' => $group, 'title' => 'Edit'],
            ['name' => $slug.'-delete', 'group' => $group, 'title' => 'Delete'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')->where('name', $permission['name'])->first();
            if ($existing) {
                $permissionIds[] = $existing->id;
                continue;
            }
            $permissionIds[] = DB::table('permissions')->insertGetId([
                'name'       => $permission['name'],
                'group'      => $permission['group'],
                'title'      => $permission['title'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sliderPermissionId = DB::table('permissions')->where('name', 'slider-view')->value('id');
        if (!$sliderPermissionId || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $sliderPermissionId)
            ->pluck('role_id')
            ->unique()
            ->all();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('department_research_items');
        if (!Schema::hasTable('permissions')) {
            return;
        }
        $names = ['research-item-view', 'research-item-create', 'research-item-edit', 'research-item-delete'];
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('name', $names)->delete();
    }
};
