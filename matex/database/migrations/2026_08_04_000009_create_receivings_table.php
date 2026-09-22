<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_schedule_id')->constrained()->cascadeOnDelete();
            $table->decimal('received_qty', 12, 3);
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->string('qad_status')->default('pending');
            $table->json('qad_payload')->nullable();
            $table->json('qad_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivings');
    }
};
