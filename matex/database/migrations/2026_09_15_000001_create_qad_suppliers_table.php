<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qad_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('qad_code')->unique(); // t_vd_addr
            $table->string('name')->nullable();   // t_ad_sort
            $table->string('address_line1')->nullable(); // t_ad_line1
            $table->string('address_line2')->nullable(); // t_ad_line2
            $table->string('city')->nullable();    // t_ad_city
            $table->string('country')->nullable(); // t_ad_country
            $table->string('contact_name')->nullable(); // t_ad_attn
            $table->string('phone')->nullable();   // t_ad_phone
            $table->string('email')->nullable();   // t_ad_email
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qad_suppliers');
    }
};
