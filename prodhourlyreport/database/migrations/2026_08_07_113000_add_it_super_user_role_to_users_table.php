<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('leader', 'group_head', 'unit_head', 'gm', 'it_super_user') NOT NULL DEFAULT 'leader'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('users')
                ->where('role', 'it_super_user')
                ->update(['role' => 'gm']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('leader', 'group_head', 'unit_head', 'gm') NOT NULL DEFAULT 'leader'");
        }
    }
};
