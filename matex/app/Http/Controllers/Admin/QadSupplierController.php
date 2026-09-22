<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Http\Controllers\Controller;
use App\Jobs\SyncQadSuppliersJob;
use App\Models\QadSupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QadSupplierController extends Controller
{
    /**
     * Unified Suppliers page — QAD's supplier/vendor master (qad_suppliers)
     * is the single source of browsing/searching here now, replacing the
     * old manually-typed Companies list for raw_mat/ohp. Each row is
     * left-joined to its local `companies` record (matched by code, the
     * same key ResolvesQadSupplier uses) so we can show whether an account
     * (Company) has been provisioned yet and how many users it has.
     */
    public function index(Request $request): Response
    {
        $category = $request->string('category')->toString();
        $search = $request->string('q')->toString();

        $suppliers = QadSupplier::query()
            ->leftJoin('companies', 'companies.code', '=', 'qad_suppliers.qad_code')
            ->selectRaw('qad_suppliers.*, companies.id as company_id, '
                .'(select count(*) from users where users.company_id = companies.id) as users_count')
            ->when($search, function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where(function ($inner) use ($like) {
                    $inner->where('qad_suppliers.qad_code', 'like', $like)
                        ->orWhere('qad_suppliers.name', 'like', $like)
                        ->orWhere('qad_suppliers.city', 'like', $like);
                });
            })
            ->when($category === 'uncategorized', fn ($q) => $q->whereNull('qad_suppliers.category'))
            ->when(
                in_array($category, [CompanyType::RawMat->value, CompanyType::Ohp->value], true),
                fn ($q) => $q->where('qad_suppliers.category', $category)
            )
            ->orderBy('qad_suppliers.name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/QadSuppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'q' => $search,
                'category' => $category,
            ],
            'lastSyncedAt' => QadSupplier::max('last_synced_at'),
            'totalSuppliers' => QadSupplier::count(),
            'uncategorizedCount' => QadSupplier::whereNull('category')->count(),
            'syncStatus' => Cache::get(SyncQadSuppliersJob::CACHE_KEY),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $current = Cache::get(SyncQadSuppliersJob::CACHE_KEY);

        if (in_array($current['status'] ?? null, ['queued', 'running'], true)) {
            return back()->with('error', 'Sync masih berjalan. Tunggu sampai selesai, lalu refresh halaman.');
        }

        Cache::put(SyncQadSuppliersJob::CACHE_KEY, [
            'status' => 'queued',
            'message' => 'Sync diantrikan. Halaman ini akan refresh otomatis sampai selesai.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'created' => 0,
            'updated' => 0,
            'synced' => 0,
        ], now()->addHours(6));

        SyncQadSuppliersJob::dispatch()->onConnection('sync')->afterResponse();

        return back()->with('success', 'Sync Supplier QAD dimulai. Halaman ini akan refresh otomatis sampai selesai.');
    }

    /**
     * Set the manual RM/OHP category — QAD's vendor master doesn't carry
     * this distinction, so it's tagged by hand here and preserved across
     * re-syncs (see QadSupplierService::upsertBatch).
     */
    public function updateCategory(Request $request, QadSupplier $qadSupplier): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['nullable', Rule::in([CompanyType::RawMat->value, CompanyType::Ohp->value])],
        ]);

        $qadSupplier->update(['category' => $data['category'] ?? null]);

        return back()->with('success', "Kategori {$qadSupplier->qad_code} diperbarui.");
    }
}
