<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu') || !Schema::hasColumn('menu', 'location')) {
            return;
        }

        if (DB::table('menu')->where('location', 'footer')->exists()) {
            return;
        }

        $items = [
            ['menu' => 'Home', 'link_value' => '/'],
            ['menu' => 'Administration', 'link_value' => '/administration'],
            ['menu' => 'Department', 'link_value' => '/department'],
            ['menu' => 'Admission', 'link_value' => '/admission'],
            ['menu' => 'Mandatory Disclosure', 'link_value' => '/mandatory-disclosure'],
            ['menu' => 'Help Desk', 'link_value' => '/help-desk'],
            ['menu' => 'Resources', 'link_value' => '/resources'],
            ['menu' => 'Placement', 'link_value' => '/placement'],
            ['menu' => 'Research', 'link_value' => '/research'],
            ['menu' => 'Alumni', 'link_value' => '/alumni'],
            ['menu' => 'Student Council', 'link_value' => '/student-council'],
            ['menu' => 'Contact', 'link_value' => '/contact'],
            ['menu' => 'Careers', 'link_value' => '/careers'],
            ['menu' => 'Achievements', 'link_value' => '/achievements'],
            ['menu' => 'Audit Statement', 'link_value' => '/audit-statement'],
            ['menu' => 'Gallery', 'link_value' => '/gallery'],
            ['menu' => 'Rules and Regulations', 'link_value' => '/rules-and-regulations'],
            ['menu' => 'Privacy Policy', 'link_value' => '/privacy-policy'],
            ['menu' => 'Cookie Policy', 'link_value' => '/cookie-policy'],
        ];

        $sort = 0;
        foreach ($items as $item) {
            $sort++;
            DB::table('menu')->insert([
                'menu' => $item['menu'],
                'parent_id' => 0,
                'is_parent_present' => 0,
                'status' => 0,
                'location' => 'footer',
                'sort_order' => $sort,
                'link_type' => 'custom',
                'link_value' => $item['link_value'],
                'link_section' => null,
                'open_in_new_tab' => 0,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('menu') || !Schema::hasColumn('menu', 'location')) {
            return;
        }

        DB::table('menu')->where('location', 'footer')->delete();
    }
};
