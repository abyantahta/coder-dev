<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('department_qad_configs', function (Blueprint $table) {
            $table->string('location', 20)->nullable()->after('site_code')->comment('receivePurchaseOrder lineDetail.location, e.g. RAWMAT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_qad_configs', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
