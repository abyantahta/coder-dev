<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // procurement_requests sekarang hanya menangani 2 tahap: section (dept head) -> ga.
        // Tahap manager & director dipindah ke purchase_requests (lihat migrasi extend_purchase_requests_for_ga).
        DB::statement("UPDATE procurement_requests SET status = 'pending_section' WHERE status IN ('pending_manager','pending_director','approved','sent_to_qad','completed')");

        DB::statement("ALTER TABLE procurement_requests MODIFY status ENUM(
            'draft','pending_section','pending_ga','aggregated','rejected'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropForeign(['manager_by']);
            $table->dropForeign(['director_by']);
            $table->dropColumn(['manager_by', 'manager_at', 'manager_notes']);
            $table->dropColumn(['director_by', 'director_at', 'director_notes']);
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('ga_by')->nullable()->after('section_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('ga_at')->nullable()->after('ga_by');
            $table->text('ga_notes')->nullable()->after('ga_at');
            $table->foreignId('aggregated_into_pr_id')->nullable()->after('ga_notes')->constrained('purchase_requests')->nullOnDelete();
            $table->timestamp('aggregated_at')->nullable()->after('aggregated_into_pr_id');
        });

        DB::statement("ALTER TABLE procurement_requests MODIFY rejected_stage VARCHAR(20) NULL COMMENT 'section|ga'");
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropForeign(['ga_by']);
            $table->dropForeign(['aggregated_into_pr_id']);
            $table->dropColumn(['ga_by', 'ga_at', 'ga_notes', 'aggregated_into_pr_id', 'aggregated_at']);
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('manager_by')->nullable()->after('section_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_at')->nullable()->after('manager_by');
            $table->text('manager_notes')->nullable()->after('manager_at');
            $table->foreignId('director_by')->nullable()->after('manager_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('director_at')->nullable()->after('director_by');
            $table->text('director_notes')->nullable()->after('director_at');
        });

        DB::statement("ALTER TABLE procurement_requests MODIFY status ENUM(
            'draft','pending_section','pending_manager','pending_director',
            'approved','sent_to_qad','rejected','completed'
        ) NOT NULL DEFAULT 'draft'");
    }
};
