<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up()
    {
        Schema::create('department_event_items', function (Blueprint $table) {
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

            $table->index(['department_id', 'type', 'status', 'sort_order'], 'evt_dept_type_status_sort_idx');
        });

        $this->migrateJson();
        $this->seedPermissions();
        $this->refreshPermissionCache();
    }

    private function migrateJson(): void
    {
        if (!Schema::hasTable('department_events') || !Schema::hasTable('department_event_items')) {
            return;
        }

        $languageId = DB::table('languages')->orderBy('id')->value('id') ?: 1;
        $rows = DB::table('department_events')->get();

        foreach ($rows as $row) {
            $departmentId = $row->departmentId ?? null;
            $payload = json_decode($row->eventDetails ?? '[]', true);
            if (!is_array($payload) || empty($payload)) {
                continue;
            }

            $sort = 0;
            foreach ($payload as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $image1 = $entry['image1'] ?? $entry['imageFile'] ?? $entry['image'] ?? null;
                $image2 = $entry['image2'] ?? null;
                $description = $entry['description'] ?? null;
                $title = $entry['title'] ?? null;

                if ($title === null && is_string($description) && trim($description) !== '') {
                    $title = mb_substr(trim(strip_tags($description)), 0, 80);
                }

                DB::table('department_event_items')->insert([
                    'language_id'   => $languageId,
                    'department_id' => $departmentId,
                    'type'          => 'events',
                    'year'          => null,
                    'title'         => $title,
                    'description'   => $description,
                    'file'          => null,
                    'image'         => null,
                    'meta'          => json_encode([
                        'image1' => $image1,
                        'image2' => $image2,
                    ], JSON_UNESCAPED_UNICODE),
                    'sort_order'    => $sort++,
                    'status'        => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $slug = 'event-item';
        $group = 'Event Item';
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

        if (!Schema::hasTable('role_has_permissions')) {
            return;
        }

        $sourcePermissionIds = DB::table('permissions')
            ->whereIn('name', ['slider-view', 'activities-view', 'achievements-view'])
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $sourcePermissionIds)
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

    private function refreshPermissionCache(): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down()
    {
        Schema::dropIfExists('department_event_items');

        if (!Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            'event-item-view',
            'event-item-create',
            'event-item-edit',
            'event-item-delete',
        ];
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('name', $names)->delete();
    }
};
