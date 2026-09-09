<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row config — URL QAD SOAP (QXI/QdocWebService) & WSA supaya bisa
     * diubah dari UI (mis. port/host QAD berubah) tanpa perlu edit .env + deploy.
     * Kosong = pakai default dari .env (lihat QadSoapService).
     */
    public function up(): void
    {
        Schema::create('qad_settings', function (Blueprint $table) {
            $table->id();
            $table->string('qad_soap_url')->nullable();
            $table->string('qad_wsa_url')->nullable();
            $table->timestamps();
        });

        DB::table('qad_settings')->insert([
            'qad_soap_url' => null,
            'qad_wsa_url'  => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('qad_settings');
    }
};
