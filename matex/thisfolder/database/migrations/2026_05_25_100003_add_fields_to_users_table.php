<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->string('department')->default('IT')->after('role');
            $table->foreignId('unit_id')->nullable()->after('department')
                ->constrained('maintenance_units')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->after('unit_id')
                ->constrained('maintenance_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['group_id']);
            $table->dropColumn(['role', 'department', 'unit_id', 'group_id']);
        });
    }
};
