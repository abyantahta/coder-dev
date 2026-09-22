<?php

namespace App\Console\Commands;

use App\Services\Qad\QadSupplierService;
use Illuminate\Console\Command;
use Throwable;

class SyncQadSuppliers extends Command
{
    protected $signature = 'qad:sync-suppliers';

    protected $description = 'Sync the QAD supplier (vendor address) master into the local qad_suppliers cache';

    public function handle(QadSupplierService $service): int
    {
        $this->info('Syncing supplier master from QAD…');

        try {
            $result = $service->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Done — %d synced (%d created, %d updated)',
            $result['synced'],
            $result['created'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }
}
