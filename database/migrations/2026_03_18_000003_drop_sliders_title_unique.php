<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sliders')) {
            return;
        }

        // Allow same slide titles across departments and repeated captions on home.
        try {
            Schema::table('sliders', function ($table) {
                $table->dropUnique('sliders_title_unique');
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE `sliders` DROP INDEX `sliders_title_unique`');
            } catch (\Throwable $e2) {
                // ignore if already dropped
            }
        }
    }

    public function down(): void
    {
        // no-op: restoring uniqueness would fail with duplicate titles
    }
};
