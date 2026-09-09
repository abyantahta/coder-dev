<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY trans_type ENUM(
            'goods_out','goods_in','memo_in','memo_out','goods_return','ga_checkout'
        ) NOT NULL");

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('requester_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Karyawan yang barangnya diambilkan (khusus ga_checkout)');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->foreignId('procurement_request_detail_id')->nullable()->after('item_id')
                ->constrained('procurement_request_details')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropForeign(['procurement_request_detail_id']);
            $table->dropColumn('procurement_request_detail_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['requester_id']);
            $table->dropColumn('requester_id');
        });

        DB::statement("ALTER TABLE transactions MODIFY trans_type ENUM(
            'goods_out','goods_in','memo_in','memo_out','goods_return'
        ) NOT NULL");
    }
};
