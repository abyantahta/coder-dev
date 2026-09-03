<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);

            // QAD sync preparation: master data is entered manually for now,
            // but every record tracks its external QAD reference and sync state
            // so a future sync job can reconcile without a schema change.
            $table->string('qad_code')->nullable()->unique();
            $table->enum('source', ['manual', 'qad'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lines');
    }
};
