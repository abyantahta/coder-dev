<?php

namespace App\Jobs;

use App\Services\Sync\QadSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncQadLinesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public const CACHE_KEY = 'qad.sync.lines';

    public function handle(QadSyncService $service): void
    {
        ignore_user_abort(true);
        $service->raiseLimits();

        Cache::put(self::CACHE_KEY, [
            'status' => 'running',
            'message' => 'Syncing lines from QAD…',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'created' => 0,
            'updated' => 0,
            'synced' => 0,
        ], now()->addHours(6));

        try {
            $result = $service->syncLines();

            Cache::put(self::CACHE_KEY, [
                'status' => 'ok',
                'message' => "Sync Line berhasil — {$result['synced']} diproses ({$result['created']} baru, {$result['updated']} diperbarui).",
                'started_at' => Cache::get(self::CACHE_KEY)['started_at'] ?? now()->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'created' => $result['created'],
                'updated' => $result['updated'],
                'synced' => $result['synced'],
            ], now()->addHours(6));
        } catch (Throwable $e) {
            Log::error('QAD line sync failed', ['exception' => $e]);

            Cache::put(self::CACHE_KEY, [
                'status' => 'failed',
                'message' => 'Sync Line gagal: '.$e->getMessage(),
                'started_at' => Cache::get(self::CACHE_KEY)['started_at'] ?? null,
                'finished_at' => now()->toIso8601String(),
                'created' => 0,
                'updated' => 0,
                'synced' => 0,
            ], now()->addHours(6));

            throw $e;
        }
    }

    public function uniqueId(): string
    {
        return 'qad-sync-lines';
    }
}
