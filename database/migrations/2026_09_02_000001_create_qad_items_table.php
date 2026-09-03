<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qad_items', function (Blueprint $table) {
            $table->id();
            $table->string('qad_code')->unique();      // t_pt_part
            $table->string('description')->nullable(); // t_pt_desc1
            $table->string('part_number')->nullable();  // t_pt_desc2
            $table->string('qad_group')->nullable();    // t_pt_group
            $table->string('prod_line')->nullable();    // t_pt_prod_line
            $table->string('qad_status')->nullable();    // t_pt_status (raw QAD code, unmapped)
            $table->string('location')->nullable();      // t_pt_location
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['prod_line', 'qad_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qad_items');
    }
};
