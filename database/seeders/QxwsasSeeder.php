<?php

namespace Database\Seeders;

use App\Models\Qxwsas;
use Illuminate\Database\Seeder;

class QxwsasSeeder extends Seeder
{
    /**
     * Seed the QAD WSA connection row used by App\Services\Qad\QadItemService.
     */
    public function run(): void
    {
        Qxwsas::updateOrCreate(['id' => 1], [
            'qxwsa_wsa_url' => 'http://qadeesdi.site:25079/wsa/wsaprod',
            'qxwsa_wsa_path' => 'http://ws.imi.co.id/wsaprod',
            'qxwsa_wsa_domain' => '7000',
            'qxwsa_qxtend_url' => 'http://qadeesdi.site:24079/qxi/services/QdocWebService',
        ]);
    }
}
