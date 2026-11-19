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
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('name', 'events-view')->value('id');
        if (!$permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'       => 'events-view',
                'group'      => 'Events',
                'title'      => 'View',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('role_has_permissions')) {
            return;
        }

        $sourcePermissionIds = DB::table('permissions')
            ->whereIn('name', ['newsletters-view', 'magazines-view', 'slider-view', 'event-item-view'])
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $sourcePermissionIds)
            ->pluck('role_id')
            ->unique()
            ->all();

        foreach ($roleIds as $roleId) {
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

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
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

        $id = DB::table('permissions')->where('name', 'events-view')->value('id');
        if ($id && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        }
        DB::table('permissions')->where('name', 'events-view')->delete();
    }
};
