<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QadItem;
use App\Services\Qad\QadItemService;
use Illuminate\Http\Request;
use Throwable;

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

        return view('items.index', compact('items', 'lastSyncedAt', 'totalItems'));
    }

    public function sync(QadItemService $service)
    {
        try {
            $result = $service->sync();
        } catch (Throwable $e) {
            return back()->with('error', 'Sync gagal: '.$e->getMessage());
        }

        return back()->with('success',
            "Sync selesai: {$result['synced']} item diproses ({$result['created']} baru, {$result['updated']} diperbarui).");
    }
}
