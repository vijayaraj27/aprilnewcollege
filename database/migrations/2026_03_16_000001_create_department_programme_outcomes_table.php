<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_programme_outcomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('type', 10); // peo | po | pso
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->index(['department_id', 'type', 'status', 'sort_order'], 'dept_type_status_sort_idx');
        });

        $this->migrateFromAboutUsJson();
        $this->seedPermissions();
    }

    /**
     * One-time migrate of JSON arrays from department_about_us.
     */
    private function migrateFromAboutUsJson(): void
    {
        if (!Schema::hasTable('department_about_us')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $rows = DB::table('department_about_us')->get();

        $typeMap = [
            'peo' => 'programmeEducationalObjectives',
            'po'  => 'programmeOutcomes',
            'pso' => 'programmeSpecificOutcomes',
        ];

        foreach ($rows as $about) {
            foreach ($typeMap as $type => $column) {
                $items = json_decode($about->{$column} ?? '[]', true);
                if (!is_array($items) || empty($items)) {
                    continue;
                }

                $sort = 0;
                foreach ($items as $item) {
                    $title = null;
                    $description = null;

                    if (is_array($item)) {
                        $title = isset($item['title']) ? trim((string) $item['title']) : null;
                        $description = isset($item['description'])
                            ? trim((string) $item['description'])
                            : trim((string) ($item[0] ?? ''));
                        if ($title === '') {
                            $title = null;
                        }
                    } else {
                        $description = trim((string) $item);
                    }

                    if ($description === '' && $title === null) {
                        continue;
                    }

                    DB::table('department_programme_outcomes')->insert([
                        'language_id'   => $about->language_id ?: $languageId,
                        'department_id' => $about->departmentId,
                        'type'          => $type,
                        'title'         => $title,
                        'description'   => $description ?: $title,
                        'sort_order'    => $sort++,
                        'status'        => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Add CRUD permissions and assign to roles that already manage Slider.
     */
    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            ['name' => 'programme-outcome-view', 'group' => 'Programme Outcome', 'title' => 'View'],
            ['name' => 'programme-outcome-create', 'group' => 'Programme Outcome', 'title' => 'Create'],
            ['name' => 'programme-outcome-edit', 'group' => 'Programme Outcome', 'title' => 'Edit'],
            ['name' => 'programme-outcome-delete', 'group' => 'Programme Outcome', 'title' => 'Delete'],
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_programme_outcomes');

        if (Schema::hasTable('permissions')) {
            $names = [
                'programme-outcome-view',
                'programme-outcome-create',
                'programme-outcome-edit',
                'programme-outcome-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
