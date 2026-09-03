<?php

namespace App\Console\Commands;

use App\Services\Qad\QadItemService;
use Illuminate\Console\Command;
use Throwable;

class SyncQadItems extends Command
{
    protected $signature = 'qad:sync-items';

    protected $description = 'Sync the QAD item master into the local qad_items cache';

    public function handle(QadItemService $service): int
    {
        $this->info('Syncing item master from QAD…');

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
