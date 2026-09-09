<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('source', 20)->default('min_stock')->after('pr_no')->comment('min_stock|ga_aggregation');

            $table->foreignId('director_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('director_at')->nullable()->after('director_by');
            $table->text('director_notes')->nullable()->after('director_at');
            $table->text('reject_reason')->nullable()->after('director_notes');

            $table->string('qad_req_no', 50)->nullable()->after('reject_reason')->comment('No. Requisition di QAD');
            $table->timestamp('sent_to_qad_at')->nullable()->after('qad_req_no');
            $table->text('qad_sync_message')->nullable()->after('sent_to_qad_at');

            $table->foreignId('received_by')->nullable()->after('qad_sync_message')->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('received_by');
            $table->timestamp('distributed_at')->nullable()->after('received_at');
        });

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','pending_director','approved','sent_to_qad',
            'received','distributing','completed','cancelled','rejected'
        ) NOT NULL DEFAULT 'draft'");

        // item_id jadi nullable (baris sumber ga_aggregation memakai procurement_item_id, bukan item_id)
        DB::statement('ALTER TABLE purchase_request_details MODIFY item_id BIGINT UNSIGNED NULL');

        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->foreignId('procurement_item_id')->nullable()->after('item_id')->constrained('procurement_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->dropForeign(['procurement_item_id']);
            $table->dropColumn('procurement_item_id');
        });

        DB::statement('ALTER TABLE purchase_request_details MODIFY item_id BIGINT UNSIGNED NOT NULL');

        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM(
            'draft','submitted','completed','cancelled'
        ) NOT NULL DEFAULT 'draft'");

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['director_by']);
            $table->dropForeign(['received_by']);
            $table->dropColumn([
                'source', 'director_by', 'director_at', 'director_notes', 'reject_reason',
                'qad_req_no', 'sent_to_qad_at', 'qad_sync_message',
                'received_by', 'received_at', 'distributed_at',
            ]);
        });
    }
};
