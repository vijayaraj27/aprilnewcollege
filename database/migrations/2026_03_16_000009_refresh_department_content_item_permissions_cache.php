<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $itemPermissionIds = DB::table('permissions')
            ->where('name', 'like', '%-item-%')
            ->pluck('id');

        if ($itemPermissionIds->isEmpty()) {
            return;
        }

        $sourcePermissionNames = [
            'slider-view',
            'placements-view',
            'videomaterials-view',
            'coursematerials-view',
            'newsletters-view',
            'magazines-view',
            'syllabus-view',
            'library-view',
            'research-view',
            'publications-view',
            'activities-view',
            'achievements-view',
        ];

        $sourcePermissionIds = DB::table('permissions')
            ->whereIn('name', $sourcePermissionNames)
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $sourcePermissionIds)
            ->pluck('role_id')
            ->unique()
            ->values();

        foreach ($roleIds as $roleId) {
            foreach ($itemPermissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // ignore if command unavailable
        }
    }

    public function down()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
