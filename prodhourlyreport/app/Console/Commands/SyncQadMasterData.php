<?php

namespace App\Console\Commands;

use App\Services\Sync\QadSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncQadMasterData extends Command
{
    protected $signature = 'qad:sync
                            {--lines : Sync lines only}
                            {--products : Sync products only}';

    protected $description = 'Sync lines and products from QAD (models remain local)';

    public function handle(QadSyncService $service): int
    {
        $service->raiseLimits();

        $onlyLines = (bool) $this->option('lines');
        $onlyProducts = (bool) $this->option('products');
        $runAll = ! $onlyLines && ! $onlyProducts;

        $ok = true;

        if ($runAll || $onlyLines) {
            $ok = $this->runSync('Lines', fn () => $service->syncLines()) && $ok;
        }

        if ($runAll || $onlyProducts) {
            $ok = $this->runSync('Products', fn () => $service->syncProducts()) && $ok;
        }

        if ($runAll) {
            $models = $service->syncProductModels();
            $this->line("Models: {$models['status']} — {$models['message']}");
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  callable(): array{synced: int, created: int, updated: int, status: string, message?: string}  $callback
     */
    private function runSync(string $label, callable $callback): bool
    {
        $this->info("Syncing {$label} from QAD…");

        try {
            $result = $callback();
        } catch (Throwable $e) {
            $this->error("{$label}: {$e->getMessage()}");

            return false;
        }

        $this->line(sprintf(
            '%s: %s — %d synced (%d created, %d updated)',
            $label,
            $result['status'],
            $result['synced'],
            $result['created'],
            $result['updated'],
        ));

        return true;
    }
}
