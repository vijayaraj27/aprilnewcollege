<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('department_syllabus_items', function (Blueprint $table) {
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
            $table->index(['department_id', 'type', 'status', 'year', 'sort_order'], 'syl_dept_type_status_year_sort_idx');
        });

        Schema::create('department_library_items', function (Blueprint $table) {
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
            $table->index(['department_id', 'type', 'status', 'year', 'sort_order'], 'lib_dept_type_status_year_sort_idx');
        });

        $this->migrateSyllabus();
        $this->migrateLibrary();
        $this->seedPermissions('syllabus-item', 'Syllabus Item');
        $this->seedPermissions('library-item', 'Library Item');
    }

    private function migrateSyllabus(): void
    {
        if (!Schema::hasTable('department_syllabus')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $map = [
            'syllabus' => 'syllabus',
            'regulation' => 'regulation',
            'questionBank' => 'question-bank',
        ];

        foreach (DB::table('department_syllabus')->get() as $row) {
            foreach ($map as $column => $type) {
                $items = json_decode($row->{$column} ?? '[]', true);
                if (!is_array($items) || empty($items)) {
                    continue;
                }
                $sort = 0;
                foreach (array_values($items) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $title = trim((string) ($item['Title'] ?? $item['title'] ?? ''));
                    $meta = [
                        'programType' => isset($item['programType']) ? trim((string) $item['programType']) : null,
                        'pdfLink' => isset($item['pdfLink']) ? trim((string) $item['pdfLink']) : null,
                    ];
                    if ($type === 'regulation') {
                        $meta['regulationType'] = isset($item['regulationType']) ? trim((string) $item['regulationType']) : null;
                    }
                    if ($title === '' && empty(array_filter($meta))) {
                        continue;
                    }
                    DB::table('department_syllabus_items')->insert([
                        'language_id'   => $languageId,
                        'department_id' => $row->departmentId ?? null,
                        'type'          => $type,
                        'year'          => null,
                        'title'         => $title !== '' ? $title : null,
                        'description'   => $item['description'] ?? null,
                        'file'          => null,
                        'image'         => null,
                        'meta'          => json_encode($meta, JSON_UNESCAPED_UNICODE),
                        'sort_order'    => $sort++,
                        'status'        => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }

    private function migrateLibrary(): void
    {
        if (!Schema::hasTable('department_libraries')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;

        foreach (DB::table('department_libraries')->get() as $row) {
            $items = json_decode($row->record ?? '[]', true);
            if (!is_array($items) || empty($items)) {
                continue;
            }
            $sort = 0;
            foreach (array_values($items) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? ''));
                $noOfBooks = isset($item['noOfBooks']) ? trim((string) $item['noOfBooks']) : null;
                if ($title === '' && ($noOfBooks === null || $noOfBooks === '')) {
                    continue;
                }
                DB::table('department_library_items')->insert([
                    'language_id'   => $languageId,
                    'department_id' => $row->departmentId ?? null,
                    'type'          => 'record',
                    'year'          => null,
                    'title'         => $title !== '' ? $title : null,
                    'description'   => null,
                    'file'          => null,
                    'image'         => null,
                    'meta'          => json_encode(['noOfBooks' => $noOfBooks], JSON_UNESCAPED_UNICODE),
                    'sort_order'    => $sort++,
                    'status'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
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
        Schema::dropIfExists('department_syllabus_items');
        Schema::dropIfExists('department_library_items');

        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['syllabus-item', 'library-item'] as $slug) {
            $names = [$slug.'-view', $slug.'-create', $slug.'-edit', $slug.'-delete'];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
