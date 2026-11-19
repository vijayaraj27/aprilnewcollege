<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu') || !Schema::hasColumn('menu', 'menu')) {
            return;
        }

        DB::statement('ALTER TABLE `menu` MODIFY `menu` VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('menu') || !Schema::hasColumn('menu', 'menu')) {
            return;
        }

        DB::statement('ALTER TABLE `menu` MODIFY `menu` VARCHAR(50) NOT NULL');
    }
};
