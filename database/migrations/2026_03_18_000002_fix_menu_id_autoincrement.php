<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu')) {
            return;
        }

        $maxId = (int) DB::table('menu')->max('id');
        $next = max($maxId + 1, 1);

        // Ensure PRIMARY KEY + AUTO_INCREMENT on id (legacy table lacked both).
        try {
            DB::statement('ALTER TABLE `menu` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT');
        } catch (\Throwable $e) {
            // continue — may already be auto_increment or need PK first
        }

        try {
            DB::statement('ALTER TABLE `menu` ADD PRIMARY KEY (`id`)');
        } catch (\Throwable $e) {
            // primary key may already exist
        }

        try {
            DB::statement('ALTER TABLE `menu` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `menu` AUTO_INCREMENT = ' . $next);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // Non-destructive: keep autoincrement once added.
    }
};
