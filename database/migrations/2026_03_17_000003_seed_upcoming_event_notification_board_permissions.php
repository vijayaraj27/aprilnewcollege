<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $resources = [
        [
            'slug' => 'upcoming-event',
            'group' => 'Upcoming Event',
        ],
        [
            'slug' => 'notification-board',
            'group' => 'Notification Board',
        ],
    ];

    public function up()
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = [];

        foreach ($this->resources as $resource) {
            $permissions = [
                ['name' => $resource['slug'].'-view', 'group' => $resource['group'], 'title' => 'View'],
                ['name' => $resource['slug'].'-create', 'group' => $resource['group'], 'title' => 'Create'],
                ['name' => $resource['slug'].'-edit', 'group' => $resource['group'], 'title' => 'Edit'],
                ['name' => $resource['slug'].'-delete', 'group' => $resource['group'], 'title' => 'Delete'],
            ];

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
        }

        $sourcePermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'slider-view',
                'newsletters-view',
                'magazines-view',
                'events-view',
                'event-item-view',
                'about-us-view',
            ])
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $sourcePermissionIds)
            ->pluck('role_id')
            ->unique()
            ->all();

        // Always include SuperAdmin / Admin / DepartmentWebEditor when present
        foreach ([1, 2, 4] as $roleId) {
            if (DB::table('roles')->where('id', $roleId)->exists()) {
                $roleIds[] = $roleId;
            }
        }
        $roleIds = array_values(array_unique($roleIds));

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
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $names = [];
        foreach ($this->resources as $resource) {
            $names[] = $resource['slug'].'-view';
            $names[] = $resource['slug'].'-create';
            $names[] = $resource['slug'].'-edit';
            $names[] = $resource['slug'].'-delete';
        }

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('name', $names)->delete();
    }
};
