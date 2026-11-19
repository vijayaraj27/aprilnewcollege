<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('department_faculty_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('type', 20); // teaching | non-teaching
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            $table->string('qualification')->nullable();
            $table->string('email')->nullable();
            $table->string('au_fin')->nullable();
            $table->string('aicte_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->string('photo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->index(['department_id', 'type', 'status', 'sort_order'], 'faculty_dept_type_status_sort_idx');
        });

        $this->migrateFromJson();
        $this->seedPermissions();
    }

    private function migrateFromJson(): void
    {
        if (!Schema::hasTable('department_faculties')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $rows = DB::table('department_faculties')->get();

        foreach ($rows as $faculty) {
            $this->insertStaffRows(
                $faculty->departmentId,
                $languageId,
                'teaching',
                json_decode($faculty->teachingStaff ?? '[]', true)
            );
            $this->insertStaffRows(
                $faculty->departmentId,
                $languageId,
                'non-teaching',
                json_decode($faculty->nonTeachingStaff ?? '[]', true)
            );
        }
    }

    private function insertStaffRows($departmentId, $languageId, string $type, $items): void
    {
        if (!is_array($items) || empty($items)) {
            return;
        }

        $sort = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['faculty'] ?? $item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $profile = trim((string) ($item['profileId'] ?? $item['profile_url'] ?? ''));
            $profileUrl = (str_starts_with($profile, 'http://') || str_starts_with($profile, 'https://'))
                ? $profile
                : null;

            DB::table('department_faculty_members')->insert([
                'language_id'   => $languageId,
                'department_id' => $departmentId,
                'type'          => $type,
                'name'          => $name,
                'designation'   => $item['designation'] ?? null,
                'qualification' => $item['qualification'] ?? null,
                'email'         => $item['email'] ?? null,
                'au_fin'        => $item['au_fin'] ?? null,
                'aicte_id'      => $item['uniqueId'] ?? ($item['aicte_id'] ?? null),
                'profile_url'   => $profileUrl,
                'photo'         => $item['photo'] ?? null,
                'sort_order'    => isset($item['sorting']) ? (int) $item['sorting'] : $sort,
                'status'        => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $sort++;
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            ['name' => 'faculty-member-view', 'group' => 'Faculty Member', 'title' => 'View'],
            ['name' => 'faculty-member-create', 'group' => 'Faculty Member', 'title' => 'Create'],
            ['name' => 'faculty-member-edit', 'group' => 'Faculty Member', 'title' => 'Edit'],
            ['name' => 'faculty-member-delete', 'group' => 'Faculty Member', 'title' => 'Delete'],
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
        Schema::dropIfExists('department_faculty_members');

        if (Schema::hasTable('permissions')) {
            $names = [
                'faculty-member-view',
                'faculty-member-create',
                'faculty-member-edit',
                'faculty-member-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
