<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu')) {
            Schema::table('menu', function (Blueprint $table) {
                if (!Schema::hasColumn('menu', 'location')) {
                    $table->string('location', 20)->default('header')->after('status');
                }
                if (!Schema::hasColumn('menu', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('location');
                }
                if (!Schema::hasColumn('menu', 'link_type')) {
                    $table->string('link_type', 20)->default('legacy')->after('sort_order');
                }
                if (!Schema::hasColumn('menu', 'link_value')) {
                    $table->string('link_value', 500)->nullable()->after('link_type');
                }
                if (!Schema::hasColumn('menu', 'link_section')) {
                    $table->string('link_section', 100)->nullable()->after('link_value');
                }
                if (!Schema::hasColumn('menu', 'open_in_new_tab')) {
                    $table->boolean('open_in_new_tab')->default(false)->after('link_section');
                }
            });

            // Existing rows = header menus
            DB::table('menu')->whereNull('location')->orWhere('location', '')->update([
                'location' => 'header',
            ]);
            DB::table('menu')->whereNull('link_type')->orWhere('link_type', '')->update([
                'link_type' => 'legacy',
            ]);

            // Seed sort_order from current id order within each parent
            $rows = DB::table('menu')->orderBy('parent_id')->orderBy('id')->get(['id', 'parent_id']);
            $counters = [];
            foreach ($rows as $row) {
                $parent = (int) $row->parent_id;
                if (!isset($counters[$parent])) {
                    $counters[$parent] = 0;
                }
                $counters[$parent]++;
                DB::table('menu')->where('id', $row->id)->update([
                    'sort_order' => $counters[$parent],
                ]);
            }
        }

        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $groups = [
            'menu' => 'Main Menu',
            'footer-menu' => 'Footer Menu',
        ];

        $permissionIds = [];
        foreach ($groups as $slug => $group) {
            foreach (['view' => 'View', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete'] as $action => $title) {
                $name = $slug . '-' . $action;
                $existing = DB::table('permissions')->where('name', $name)->first();
                if ($existing) {
                    $permissionIds[] = $existing->id;
                    continue;
                }
                $permissionIds[] = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'group' => $group,
                    'title' => $title,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // SuperAdmin only (role id 1)
        if (DB::table('roles')->where('id', 1)->exists()) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', 1)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => 1,
                    ]);
                }
            }
        }

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasTable('role_has_permissions')) {
            $names = [
                'menu-view', 'menu-create', 'menu-edit', 'menu-delete',
                'footer-menu-view', 'footer-menu-create', 'footer-menu-edit', 'footer-menu-delete',
            ];
            $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
            try {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('menu')) {
            Schema::table('menu', function (Blueprint $table) {
                foreach (['open_in_new_tab', 'link_section', 'link_value', 'link_type', 'sort_order', 'location'] as $col) {
                    if (Schema::hasColumn('menu', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
