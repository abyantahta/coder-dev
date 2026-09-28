<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-department rule: must the assigned member attach a photo/document
     * when marking a WO complete? Off by default (current behaviour).
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('completion_attachment_required')->default(false)->after('has_unit_structure');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('completion_attachment_required');
        });
    }
};
