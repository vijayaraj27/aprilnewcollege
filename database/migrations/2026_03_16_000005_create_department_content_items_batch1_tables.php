<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $resources = [
        [
            'table' => 'department_placement_items',
            'source' => 'department_placements',
            'json' => 'studentPlaced',
            'type' => 'student-placed',
            'mode' => 'year-nested',
            'children' => 'placements',
            'metaKeys' => ['studentRegNumber', 'studentName', 'companyName'],
            'titleKey' => 'studentName',
            'permissions' => 'placement-item',
            'group' => 'Placement Item',
            'index' => 'plc_dept_type_status_year_sort_idx',
        ],
        [
            'table' => 'department_video_items',
            'source' => 'department_videos',
            'json' => 'videos',
            'type' => 'videos',
            'mode' => 'flat',
            'metaKeys' => ['YoutubeLink'],
            'titleKey' => 'title',
            'permissions' => 'video-item',
            'group' => 'Video Item',
            'index' => 'vid_dept_type_status_year_sort_idx',
        ],
        [
            'table' => 'department_course_items',
            'source' => 'department_courses',
            'json' => 'courses',
            'type' => 'courses',
            'mode' => 'flat',
            'metaKeys' => ['pdfLink'],
            'titleKey' => 'title',
            'permissions' => 'course-item',
            'group' => 'Course Item',
            'index' => 'crs_dept_type_status_year_sort_idx',
        ],
        [
            'table' => 'department_newsletter_items',
            'source' => 'department_newsletters',
            'json' => 'newsletter',
            'type' => 'newsletters',
            'mode' => 'flat-year',
            'metaKeys' => ['pdfLink'],
            'titleKey' => 'year',
            'permissions' => 'newsletter-item',
            'group' => 'Newsletter Item',
            'index' => 'nws_dept_type_status_year_sort_idx',
        ],
        [
            'table' => 'department_magazine_items',
            'source' => 'department_magazines',
            'json' => 'magazines',
            'type' => 'magazines',
            'mode' => 'flat-year',
            'metaKeys' => ['pdfLink'],
            'titleKey' => 'year',
            'permissions' => 'magazine-item',
            'group' => 'Magazine Item',
            'index' => 'mag_dept_type_status_year_sort_idx',
        ],
    ];

    public function up()
    {
        foreach ($this->resources as $resource) {
            Schema::create($resource['table'], function (Blueprint $table) use ($resource) {
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

                $table->index(['department_id', 'type', 'status', 'year', 'sort_order'], $resource['index']);
            });

            $this->migrateJson($resource);
            $this->seedPermissions($resource['permissions'], $resource['group']);
        }
    }

    private function migrateJson(array $resource): void
    {
        if (!Schema::hasTable($resource['source']) || !Schema::hasTable($resource['table'])) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $rows = DB::table($resource['source'])->get();

        foreach ($rows as $row) {
            $departmentId = $row->departmentId ?? null;
            $payload = json_decode($row->{$resource['json']} ?? '[]', true);
            if (!is_array($payload) || empty($payload)) {
                continue;
            }

            $sort = 0;

            if ($resource['mode'] === 'year-nested') {
                foreach ($payload as $yearData) {
                    if (!is_array($yearData)) {
                        continue;
                    }
                    $year = trim((string) ($yearData['year'] ?? ''));
                    if ($year === '' || strcasecmp($year, 'Other') === 0) {
                        continue;
                    }
                    $children = $yearData[$resource['children']] ?? [];
                    if (!is_array($children)) {
                        continue;
                    }
                    foreach ($children as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $meta = $this->pickMeta($item, $resource['metaKeys']);
                        $title = trim((string) ($item[$resource['titleKey']] ?? ''));
                        if ($title === '' && empty(array_filter($meta))) {
                            continue;
                        }
                        $this->insertItem($resource['table'], $languageId, $departmentId, $resource['type'], $year, $title ?: null, $meta, $sort++);
                    }
                }
                continue;
            }

            foreach (array_values($payload) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $year = null;
                if ($resource['mode'] === 'flat-year') {
                    $year = trim((string) ($item['year'] ?? ''));
                    if ($year === '' || strcasecmp($year, 'Other') === 0) {
                        continue;
                    }
                }

                $meta = $this->pickMeta($item, $resource['metaKeys']);
                $title = trim((string) ($item[$resource['titleKey']] ?? ($item['title'] ?? '')));
                $description = $item['description'] ?? null;

                if ($title === '' && empty(array_filter($meta))) {
                    continue;
                }

                $this->insertItem(
                    $resource['table'],
                    $languageId,
                    $departmentId,
                    $resource['type'],
                    $year,
                    $title !== '' ? $title : ($year ?: null),
                    $meta,
                    $sort++,
                    $description
                );
            }
        }
    }

    private function pickMeta(array $item, array $keys): array
    {
        $meta = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $item)) {
                $meta[$key] = is_string($item[$key]) ? trim($item[$key]) : $item[$key];
            }
        }
        return $meta;
    }

    private function insertItem(
        string $table,
        int $languageId,
        $departmentId,
        string $type,
        ?string $year,
        ?string $title,
        array $meta,
        int $sort,
        $description = null
    ): void {
        DB::table($table)->insert([
            'language_id'   => $languageId,
            'department_id' => $departmentId,
            'type'          => $type,
            'year'          => $year,
            'title'         => $title,
            'description'   => $description,
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
        foreach ($this->resources as $resource) {
            Schema::dropIfExists($resource['table']);

            if (!Schema::hasTable('permissions')) {
                continue;
            }

            $names = [
                $resource['permissions'].'-view',
                $resource['permissions'].'-create',
                $resource['permissions'].'-edit',
                $resource['permissions'].'-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
