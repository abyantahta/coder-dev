<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qad_suppliers', function (Blueprint $table) {
            // Manual classification (raw_mat | ohp) — QAD's vendor master
            // doesn't carry this distinction, so it's set by hand in matex
            // and preserved across re-syncs (not part of the QAD payload).
            $table->string('category')->nullable()->after('qad_code');
        });
    }

    public function down(): void
    {
        Schema::table('qad_suppliers', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
