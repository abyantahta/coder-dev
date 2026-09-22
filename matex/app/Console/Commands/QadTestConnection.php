<?php

namespace App\Console\Commands;

use App\Services\Qad\QadSoapClient;
use Illuminate\Console\Command;

class QadTestConnection extends Command
{
    protected $signature = 'qad:test-connection';

    protected $description = 'Cek konektivitas ke QAD QXtend web service (read-only, tidak kirim transaksi)';

    public function handle(): int
    {
        $config = config('qad.qxi');

        $this->info('Base URL : '.($config['base_url'] ?? '(kosong)'));
        $this->info('Domain   : '.($config['domain'] ?? '(kosong)'));
        $this->info('Username : '.($config['username'] ?? '(kosong)'));

        if (blank($config['base_url'] ?? null)) {
            $this->error('QAD_BASE_URL belum di-set di .env');

            return self::FAILURE;
        }

        $client = QadSoapClient::fromConfig();
        $result = $client->ping();

        if ($result['reachable']) {
            $this->info("Terhubung ke QAD ✔ (HTTP {$result['status']})");

            return self::SUCCESS;
        }

        $this->error("Gagal terhubung ke QAD (HTTP {$result['status']})");

        return self::FAILURE;
    }
}
