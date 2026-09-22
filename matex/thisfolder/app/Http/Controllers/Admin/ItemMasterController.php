<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncQadItemsJob;
use App\Models\QadItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemMasterController extends Controller
{
    public function index(Request $request)
    {
        $items = QadItem::query()
            ->search($request->q)
            ->orderBy('description')
            ->paginate(25)
            ->withQueryString();

        $lastSyncedAt = QadItem::max('last_synced_at');
        $totalItems = QadItem::count();
        $syncStatus = Cache::get(SyncQadItemsJob::CACHE_KEY);

        return view('items.index', compact('items', 'lastSyncedAt', 'totalItems', 'syncStatus'));
    }

    public function sync(): RedirectResponse
    {
        $current = Cache::get(SyncQadItemsJob::CACHE_KEY);

        if (in_array($current['status'] ?? null, ['queued', 'running'], true)) {
            return back()->with('error', 'Sync masih berjalan. Tunggu sampai selesai, lalu refresh halaman.');
        }

        Cache::put(SyncQadItemsJob::CACHE_KEY, [
            'status' => 'queued',
            'message' => 'Sync diantrikan. Halaman ini akan refresh otomatis sampai selesai.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'created' => 0,
            'updated' => 0,
            'synced' => 0,
        ], now()->addHours(6));

        // Run on the sync connection so this executes right after the HTTP
        // response is sent, without needing a queue:work worker running.
        SyncQadItemsJob::dispatch()->onConnection('sync')->afterResponse();

        return back()->with('success', 'Sync Item QAD dimulai. Halaman ini akan refresh otomatis sampai selesai.');
    }
}
