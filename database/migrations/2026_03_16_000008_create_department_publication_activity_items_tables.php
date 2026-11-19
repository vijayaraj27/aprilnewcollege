<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        foreach (['department_publication_items' => 'pub_dept_type_status_year_sort_idx', 'department_activity_items' => 'act_dept_type_status_year_sort_idx'] as $table => $index) {
            Schema::create($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->id();
                $blueprint->unsignedBigInteger('language_id')->nullable();
                $blueprint->unsignedBigInteger('department_id')->nullable();
                $blueprint->string('type', 40);
                $blueprint->string('year', 50)->nullable();
                $blueprint->string('title')->nullable();
                $blueprint->text('description')->nullable();
                $blueprint->string('file')->nullable();
                $blueprint->string('image')->nullable();
                $blueprint->json('meta')->nullable();
                $blueprint->integer('sort_order')->default(0);
                $blueprint->boolean('status')->default(1);
                $blueprint->timestamps();
                $blueprint->index(['department_id', 'type', 'status', 'year', 'sort_order'], $index);
            });
        }

        $this->migratePublications();
        $this->migrateActivities();
        $this->seedPermissions('publication-item', 'Publication Item');
        $this->seedPermissions('activity-item', 'Activity Item');
    }

    private function migratePublications(): void
    {
        if (!Schema::hasTable('department_publications')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;

        foreach (DB::table('department_publications')->get() as $row) {
            $departmentId = $row->departmentId ?? null;

            $patents = json_decode($row->patent ?? '[]', true);
            if (is_array($patents)) {
                $sort = 0;
                foreach (array_values($patents) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $meta = [
                        'NameOfTheInventors' => isset($item['NameOfTheInventors']) ? trim((string) $item['NameOfTheInventors']) : (isset($item['nameOfTheInventors']) ? trim((string) $item['nameOfTheInventors']) : null),
                        'titleOfThePatents' => isset($item['titleOfThePatents']) ? trim((string) $item['titleOfThePatents']) : null,
                        'patentNumber' => isset($item['patentNumber']) ? trim((string) $item['patentNumber']) : null,
                        'publishedDate' => isset($item['publishedDate']) ? trim((string) $item['publishedDate']) : null,
                    ];
                    $title = $meta['titleOfThePatents'] ?: ($meta['NameOfTheInventors'] ?: null);
                    if (!$title && empty(array_filter($meta))) {
                        continue;
                    }
                    $this->insert('department_publication_items', $languageId, $departmentId, 'patent', null, $title, $meta, $sort++);
                }
            }

            $books = json_decode($row->bookChapter ?? '[]', true);
            if (is_array($books)) {
                $sort = 0;
                foreach ($books as $yearData) {
                    if (!is_array($yearData)) {
                        continue;
                    }
                    $year = trim((string) ($yearData['year'] ?? ''));
                    if ($year === '' || strcasecmp($year, 'Other') === 0) {
                        continue;
                    }
                    $children = $yearData['bookChapters'] ?? [];
                    if (!is_array($children)) {
                        continue;
                    }
                    foreach ($children as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $bookName = trim((string) ($item['bookName'] ?? ''));
                        if ($bookName === '') {
                            continue;
                        }
                        $this->insert('department_publication_items', $languageId, $departmentId, 'book-chapter', $year, $bookName, ['bookName' => $bookName], $sort++);
                    }
                }
            }

            $journals = json_decode($row->journalPublication ?? '[]', true);
            if (is_array($journals)) {
                $sort = 0;
                foreach ($journals as $yearData) {
                    if (!is_array($yearData)) {
                        continue;
                    }
                    $year = trim((string) ($yearData['year'] ?? ''));
                    if ($year === '' || strcasecmp($year, 'Other') === 0) {
                        continue;
                    }
                    $children = $yearData['publications'] ?? [];
                    if (!is_array($children)) {
                        continue;
                    }
                    foreach ($children as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $journalName = trim((string) ($item['journalName'] ?? ''));
                        if ($journalName === '') {
                            continue;
                        }
                        $this->insert('department_publication_items', $languageId, $departmentId, 'journal', $year, $journalName, ['journalName' => $journalName], $sort++);
                    }
                }
            }

            $conferences = json_decode($row->conferenceList ?? '[]', true);
            if (is_array($conferences)) {
                $sort = 0;
                foreach (array_values($conferences) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $list = trim((string) ($item['list'] ?? ''));
                    if ($list === '') {
                        continue;
                    }
                    $this->insert('department_publication_items', $languageId, $departmentId, 'conference', null, mb_substr($list, 0, 255), ['list' => $list], $sort++);
                }
            }
        }
    }

    private function migrateActivities(): void
    {
        if (!Schema::hasTable('department_activities')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;

        foreach (DB::table('department_activities')->get() as $row) {
            $departmentId = $row->departmentId ?? null;

            $this->migrateYearNested(
                $row->departmentActivity ?? null,
                'activities',
                'department-activity',
                ['teacherName', 'programmeTitle', 'organizer', 'duration'],
                'programmeTitle',
                $languageId,
                $departmentId
            );

            $this->migrateYearNested(
                $row->studentParticipation ?? null,
                'participations',
                'student-participation',
                ['eventDate', 'eventName', 'conductedBy', 'nameOfTheStudentsParticipated'],
                'eventName',
                $languageId,
                $departmentId
            );

            $this->migrateYearNested(
                $row->interInstituteEventsWinningPrize ?? null,
                'events',
                'inter-institute-prize',
                ['eventDate', 'eventName', 'conductedBy', 'nameOfTheStudentsParticipated', 'prizeWon'],
                'eventName',
                $languageId,
                $departmentId
            );

            $visits = json_decode($row->industrialVisit ?? '[]', true);
            if (is_array($visits)) {
                $sort = 0;
                foreach (array_values($visits) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $meta = [
                        'nameOftheIndustry' => isset($item['nameOftheIndustry']) ? trim((string) $item['nameOftheIndustry']) : null,
                        'semester' => isset($item['semester']) ? trim((string) $item['semester']) : null,
                        'staffAccompanied' => isset($item['staffAccompanied']) ? trim((string) $item['staffAccompanied']) : null,
                        'Duration' => isset($item['Duration']) ? trim((string) $item['Duration']) : null,
                    ];
                    $title = $meta['nameOftheIndustry'] ?: null;
                    if (!$title && empty(array_filter($meta))) {
                        continue;
                    }
                    $this->insert('department_activity_items', $languageId, $departmentId, 'industrial-visit', null, $title, $meta, $sort++);
                }
            }
        }
    }

    private function migrateYearNested($json, string $childrenKey, string $type, array $metaKeys, string $titleKey, int $languageId, $departmentId): void
    {
        $payload = is_string($json) ? json_decode($json, true) : $json;
        if (!is_array($payload) || empty($payload)) {
            return;
        }

        $sort = 0;
        foreach ($payload as $yearData) {
            if (!is_array($yearData)) {
                continue;
            }
            $year = trim((string) ($yearData['year'] ?? ''));
            if ($year === '' || strcasecmp($year, 'Other') === 0) {
                continue;
            }
            $children = $yearData[$childrenKey] ?? [];
            if (!is_array($children) || empty($children)) {
                // try generic fallbacks
                foreach (['activities', 'participations', 'events', 'prizes', 'items'] as $alt) {
                    if (!empty($yearData[$alt]) && is_array($yearData[$alt])) {
                        $children = $yearData[$alt];
                        break;
                    }
                }
            }
            if (!is_array($children)) {
                continue;
            }
            foreach ($children as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $meta = [];
                foreach ($metaKeys as $key) {
                    if (array_key_exists($key, $item)) {
                        $meta[$key] = is_string($item[$key]) ? trim($item[$key]) : $item[$key];
                    }
                }
                $title = trim((string) ($item[$titleKey] ?? ''));
                if ($title === '' && empty(array_filter($meta, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }
                $this->insert('department_activity_items', $languageId, $departmentId, $type, $year, $title ?: null, $meta, $sort++);
            }
        }
    }

    private function insert(string $table, int $languageId, $departmentId, string $type, ?string $year, ?string $title, array $meta, int $sort): void
    {
        DB::table($table)->insert([
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

    private function seedPermissions(string $slug, string $group): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

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
        Schema::dropIfExists('department_publication_items');
        Schema::dropIfExists('department_activity_items');
        if (!Schema::hasTable('permissions')) {
            return;
        }
        foreach (['publication-item', 'activity-item'] as $slug) {
            $names = [$slug.'-view', $slug.'-create', $slug.'-edit', $slug.'-delete'];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
