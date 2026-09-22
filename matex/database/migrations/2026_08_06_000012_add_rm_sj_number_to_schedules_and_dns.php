<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->string('rm_sj_number')->nullable()->after('ohp_supplier_id');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('rm_sj_number')->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->dropColumn('rm_sj_number');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('rm_sj_number');
        });
    }
};
