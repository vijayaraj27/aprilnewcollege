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
        Schema::create('department_infrastructure_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('type', 30); // buildings
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->index(['department_id', 'type', 'status', 'sort_order'], 'infra_dept_type_status_sort_idx');
        });

        $this->migrateBuildingsJson();
        $this->seedPermissions();
    }

    private function migrateBuildingsJson(): void
    {
        if (!Schema::hasTable('department_infrastructures')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $sourceDir = public_path('uploads/infrastructures/');
        $destDir = public_path('uploads/infrastructure-items/');
        $rootSource = base_path('uploads/infrastructures/');
        $rootDest = base_path('uploads/infrastructure-items/');

        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0777, true, true);
        }
        if (!File::exists($rootDest)) {
            File::makeDirectory($rootDest, 0777, true, true);
        }

        $rows = DB::table('department_infrastructures')->get();

        foreach ($rows as $infra) {
            $items = json_decode($infra->buildings ?? '[]', true);
            if (!is_array($items) || empty($items)) {
                continue;
            }

            $sort = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $title = trim((string) ($item['title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $image = $item['imageFile'] ?? ($item['image'] ?? null);
                if (is_string($image) && $image !== '') {
                    $this->copyImageIfExists($image, $sourceDir, $destDir);
                    $this->copyImageIfExists($image, $rootSource, $rootDest);
                } else {
                    $image = null;
                }

                DB::table('department_infrastructure_items')->insert([
                    'language_id'   => $languageId,
                    'department_id' => $infra->departmentId,
                    'type'          => 'buildings',
                    'title'         => $title,
                    'description'   => $item['description'] ?? null,
                    'image'         => $image,
                    'sort_order'    => $sort++,
                    'status'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
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
            ['name' => 'infrastructure-item-view', 'group' => 'Infrastructure Item', 'title' => 'View'],
            ['name' => 'infrastructure-item-create', 'group' => 'Infrastructure Item', 'title' => 'Create'],
            ['name' => 'infrastructure-item-edit', 'group' => 'Infrastructure Item', 'title' => 'Edit'],
            ['name' => 'infrastructure-item-delete', 'group' => 'Infrastructure Item', 'title' => 'Delete'],
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
        Schema::dropIfExists('department_infrastructure_items');

        if (Schema::hasTable('permissions')) {
            $names = [
                'infrastructure-item-view',
                'infrastructure-item-create',
                'infrastructure-item-edit',
                'infrastructure-item-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('name', $names)->delete();
        }
    }
};
