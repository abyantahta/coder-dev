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
        Schema::table('approval_steps', function (Blueprint $table) {
            // Meaningful on requester_review steps: how many extra hours a
            // rework gets, added on top of whatever time was left on the
            // original deadline (or on top of "now" if it had already
            // passed). Null = fall back to the app default.
            $table->unsignedSmallInteger('rework_additional_hours')->nullable()->after('auto_advance_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropColumn('rework_additional_hours');
        });
    }
};
