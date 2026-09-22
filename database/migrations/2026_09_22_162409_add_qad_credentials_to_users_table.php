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
            // A user's own QAD login — used for actions QAD requires a real
            // named/authorized person for (e.g. SDI_eKanbanGR receiving),
            // as opposed to the shared service account PR creation uses.
            // Both null = this user can't execute those actions, only view.
            $table->string('qad_username')->nullable();
            $table->text('qad_password')->nullable(); // encrypted cast on the model
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qad_username', 'qad_password']);
        });
    }
};
