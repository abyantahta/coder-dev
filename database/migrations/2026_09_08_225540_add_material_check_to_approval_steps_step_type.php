<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE approval_steps MODIFY COLUMN step_type ENUM(
            'standard', 'spare_parts_check', 'assign', 'completion', 'requester_review', 'material_check'
        ) NOT NULL DEFAULT 'standard'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE approval_steps MODIFY COLUMN step_type ENUM(
            'standard', 'spare_parts_check', 'assign', 'completion', 'requester_review'
        ) NOT NULL DEFAULT 'standard'");
    }
};
