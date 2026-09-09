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
        Schema::table('users', function (Blueprint $table) {
            $table->string('qad_route_to_apr', 30)->nullable()->after('qad_approver_code');
            $table->string('qad_route_to_buyer', 30)->nullable()->after('qad_route_to_apr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qad_route_to_apr', 'qad_route_to_buyer']);
        });
    }
};
