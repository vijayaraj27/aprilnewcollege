<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('department_achievement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('type', 40); // staff|student|student-table|student-appreciation
            $table->string('year', 50)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('student_name')->nullable();
            $table->string('event_nature')->nullable();
            $table->string('event_name')->nullable();
            $table->string('period_date')->nullable();
            $table->string('organized_by')->nullable();
            $table->string('awards')->nullable();
            $table->string('year_of_student')->nullable();
            $table->string('project_name')->nullable();
            $table->string('supervisor')->nullable();
            $table->string('result_status')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->index(['department_id', 'type', 'status', 'year', 'sort_order'], 'ach_dept_type_status_year_sort_idx');
        });

        $this->migrateAchievementsJson();
        $this->seedPermissions();
    }

    private function migrateAchievementsJson(): void
    {
        if (!Schema::hasTable('department_achievements')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $sourceDir = public_path('uploads/achievements/');
        $destDir = public_path('uploads/achievement-items/');
        $rootSource = base_path('uploads/achievements/');
        $rootDest = base_path('uploads/achievement-items/');

        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0777, true, true);
        }
        if (!File::exists($rootDest)) {
            File::makeDirectory($rootDest, 0777, true, true);
        }

        $rows = DB::table('department_achievements')->get();

        foreach ($rows as $row) {
            $departmentId = $row->departmentId ?? null;
            $this->migrateStaff($row, $languageId, $departmentId);
            $this->migrateStudent($row, $languageId, $departmentId, $sourceDir, $destDir, $rootSource, $rootDest);
            $this->migrateStudentTable($row, $languageId, $departmentId);
            $this->migrateAppreciation($row, $languageId, $departmentId);
        }
    }

    private function migrateStaff(object $row, int $languageId, $departmentId): void
    {
        $items = json_decode($row->staffAchievements ?? '[]', true);
        if (!is_array($items) || empty($items)) {
            return;
        }

        $sort = 0;
        foreach ($items as $item) {
            $text = is_string($item) ? trim($item) : trim((string) ($item['description'] ?? $item['title'] ?? ''));
            if ($text === '') {
                continue;
            }

            DB::table('department_achievement_items')->insert([
                'language_id'   => $languageId,
                'department_id' => $departmentId,
                'type'          => 'staff',
                'year'          => null,
                'title'         => null,
                'description'   => $text,
                'image'         => null,
                'sort_order'    => $sort++,
                'status'        => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    private function migrateStudent(
        object $row,
        int $languageId,
        $departmentId,
        string $sourceDir,
        string $destDir,
        string $rootSource,
        string $rootDest
    ): void {
        $years = json_decode($row->studentAchievements ?? '[]', true);
        if (!is_array($years) || empty($years)) {
            return;
        }

        $sort = 0;
        foreach ($years as $yearData) {
            if (!is_array($yearData)) {
                continue;
            }
            $year = trim((string) ($yearData['year'] ?? ''));
            $achievements = $yearData['achievements'] ?? [];
            if (!is_array($achievements)) {
                continue;
            }

            foreach ($achievements as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? ''));
                $description = $item['description'] ?? null;
                if ($title === '' && empty($description)) {
                    continue;
                }

                $image = $item['image'] ?? null;
                if (is_string($image) && $image !== '') {
                    $this->copyImageIfExists($image, $sourceDir, $destDir);
                    $this->copyImageIfExists($image, $rootSource, $rootDest);
                } else {
                    $image = null;
                }

                DB::table('department_achievement_items')->insert([
                    'language_id'   => $languageId,
                    'department_id' => $departmentId,
                    'type'          => 'student',
                    'year'          => $year !== '' ? $year : null,
                    'title'         => $title !== '' ? $title : null,
                    'description'   => $description,
                    'image'         => $image,
                    'sort_order'    => $sort++,
                    'status'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    private function migrateStudentTable(object $row, int $languageId, $departmentId): void
    {
        $years = json_decode($row->studentAchievementsTableFormat ?? '[]', true);
        if (!is_array($years) || empty($years)) {
            return;
        }

        $sort = 0;
        foreach ($years as $yearData) {
            if (!is_array($yearData)) {
                continue;
            }
            $year = trim((string) ($yearData['year'] ?? ''));
            $achievements = $yearData['achievements'] ?? [];
            if (!is_array($achievements)) {
                continue;
            }

            foreach ($achievements as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $studentName = trim((string) ($item['studentName'] ?? ''));
                if ($studentName === '' && empty($item['eventName'])) {
                    continue;
                }

                DB::table('department_achievement_items')->insert([
                    'language_id'   => $languageId,
                    'department_id' => $departmentId,
                    'type'          => 'student-table',
                    'year'          => $year !== '' ? $year : null,
                    'student_name'  => $studentName !== '' ? $studentName : null,
                    'event_nature'  => $item['eventNature'] ?? null,
                    'event_name'    => $item['eventName'] ?? null,
                    'period_date'   => $item['periodDate'] ?? null,
                    'organized_by'  => $item['organizedBy'] ?? null,
                    'awards'        => $item['awards'] ?? null,
                    'sort_order'    => $sort++,
                    'status'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    private function migrateAppreciation(object $row, int $languageId, $departmentId): void
    {
        $years = json_decode($row->studentAchievementsAppeciationList ?? '[]', true);
        if (!is_array($years) || empty($years)) {
            return;
        }

        $sort = 0;
        foreach ($years as $yearData) {
            if (!is_array($yearData)) {
                continue;
            }
            $year = trim((string) ($yearData['year'] ?? ''));
            $appreciations = $yearData['appreciations'] ?? [];
            if (!is_array($appreciations)) {
                continue;
            }

            foreach ($appreciations as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $studentName = trim((string) ($item['studentName'] ?? ''));
                if ($studentName === '' && empty($item['eventName'])) {
                    continue;
                }

                DB::table('department_achievement_items')->insert([
                    'language_id'     => $languageId,
                    'department_id'   => $departmentId,
                    'type'            => 'student-appreciation',
                    'year'            => $year !== '' ? $year : null,
                    'student_name'    => $studentName !== '' ? $studentName : null,
                    'year_of_student' => $item['yearOfStudent'] ?? null,
                    'event_name'      => $item['eventName'] ?? null,
                    'project_name'    => $item['projectName'] ?? null,
                    'supervisor'      => $item['supervisor'] ?? null,
                    'result_status'   => $item['status'] ?? ($item['achievement'] ?? null),
                    'description'     => $item['achievement'] ?? null,
                    'sort_order'      => $sort++,
                    'status'          => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    private function copyImageIfExists(string $filename, string $fromDir, string $toDir): void
    {
        $from = rtrim($fromDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $to = rtrim($toDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (File::isFile($from) && !File::isFile($to)) {
            File::copy($from, $to);
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            ['name' => 'achievement-item-view', 'group' => 'Achievement Item', 'title' => 'View'],
            ['name' => 'achievement-item-create', 'group' => 'Achievement Item', 'title' => 'Create'],
            ['name' => 'achievement-item-edit', 'group' => 'Achievement Item', 'title' => 'Edit'],
            ['name' => 'achievement-item-delete', 'group' => 'Achievement Item', 'title' => 'Delete'],
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
        Schema::dropIfExists('department_achievement_items');

        if (Schema::hasTable('permissions')) {
            $names = [
                'achievement-item-view',
                'achievement-item-create',
                'achievement-item-edit',
                'achievement-item-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
