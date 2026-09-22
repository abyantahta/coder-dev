<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreForecastRequest;
use App\Models\Company;
use App\Models\Forecast;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ForecastController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Forecast::class);

        $user = $request->user();
        $nextMonth = now()->addMonthNoOverflow()->startOfMonth();

        $forecasts = Forecast::query()
            ->with(['supplierRm:id,code,name', 'uploader:id,name', 'items'])
            ->withCount('items')
            ->when(
                $user->hasRole(UserRole::SupplierRm),
                fn ($q) => $q->where('supplier_rm_id', $user->company_id)
            )
            ->when(
                $request->filled('supplier_rm_id'),
                fn ($q) => $q->where('supplier_rm_id', $request->integer('supplier_rm_id'))
            )
            ->when(
                preg_match('/^\d{4}-\d{2}$/', $request->string('period')->toString()),
                fn ($q) => $q->whereDate('period_month', $this->periodToDate($request->string('period')->toString()))
            )
            ->orderByDesc('period_month')
            ->orderBy('supplier_rm_id')
            ->paginate(15)
            ->withQueryString();

        $nextMonthForecast = null;
        if ($user->hasRole(UserRole::SupplierRm) && $user->company_id) {
            $nextMonthForecast = Forecast::query()
                ->with(['supplierRm:id,code,name', 'items.item:id,item_number,description,uom'])
                ->where('supplier_rm_id', $user->company_id)
                ->whereDate('period_month', $nextMonth)
                ->first();
        }

        return Inertia::render('Forecast/Index', [
            'forecasts' => $forecasts,
            'nextMonthForecast' => $nextMonthForecast,
            'nextMonth' => $nextMonth->format('Y-m'),
            'canUpload' => $user->can('create', Forecast::class),
            'filters' => [
                'supplier_rm_id' => $request->input('supplier_rm_id'),
                'period' => $request->string('period')->toString(),
            ],
            'suppliersRm' => $user->hasRole(UserRole::Purchasing, UserRole::Admin)
                ? Company::rawMat()->active()->orderBy('name')->get(['id', 'code', 'name'])
                : [],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Forecast::class);

        $nextMonth = now()->addMonthNoOverflow()->startOfMonth()->format('Y-m');

        return Inertia::render('Forecast/Form', [
            'forecast' => null,
            'defaultPeriod' => $request->string('period')->toString() ?: $nextMonth,
            'defaultSupplierId' => $request->input('supplier_rm_id'),
            'suppliersRm' => Company::rawMat()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::active()
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'description', 'uom']),
        ]);
    }

    public function store(StoreForecastRequest $request): RedirectResponse
    {
        $forecast = $this->persist($request, new Forecast);

        return redirect()
            ->route('forecasts.show', $forecast)
            ->with('success', 'Forecast berhasil diunggah. Supplier RM dapat melihat forecast bulan tersebut.');
    }

    public function show(Forecast $forecast): Response
    {
        $this->authorize('view', $forecast);

        $forecast->load([
            'supplierRm:id,code,name',
            'uploader:id,name',
            'items.item:id,item_number,description,uom',
        ]);

        return Inertia::render('Forecast/Show', [
            'forecast' => $forecast,
            'fileUrl' => $forecast->file_path ? route('forecasts.download', $forecast) : null,
            'canManage' => request()->user()->can('update', $forecast),
            'isNextMonth' => $forecast->period_month->format('Y-m') === now()->addMonthNoOverflow()->format('Y-m'),
        ]);
    }

    public function edit(Forecast $forecast): Response
    {
        $this->authorize('update', $forecast);

        $forecast->load(['items']);

        return Inertia::render('Forecast/Form', [
            'forecast' => $forecast,
            'defaultPeriod' => $forecast->period_month->format('Y-m'),
            'defaultSupplierId' => $forecast->supplier_rm_id,
            'suppliersRm' => Company::rawMat()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::active()
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'description', 'uom']),
        ]);
    }

    public function update(StoreForecastRequest $request, Forecast $forecast): RedirectResponse
    {
        $this->authorize('update', $forecast);

        $forecast = $this->persist($request, $forecast);

        return redirect()
            ->route('forecasts.show', $forecast)
            ->with('success', 'Forecast berhasil diperbarui.');
    }

    public function destroy(Forecast $forecast): RedirectResponse
    {
        $this->authorize('delete', $forecast);

        if ($forecast->file_path) {
            Storage::disk('public')->delete($forecast->file_path);
        }

        $forecast->delete();

        return redirect()
            ->route('forecasts.index')
            ->with('success', 'Forecast berhasil dihapus.');
    }

    public function download(Forecast $forecast): StreamedResponse
    {
        $this->authorize('download', $forecast);

        abort_unless($forecast->file_path && Storage::disk('public')->exists($forecast->file_path), 404);

        return Storage::disk('public')->download(
            $forecast->file_path,
            $forecast->original_filename ?: basename($forecast->file_path)
        );
    }

    private function persist(StoreForecastRequest $request, Forecast $forecast): Forecast
    {
        return DB::transaction(function () use ($request, $forecast) {
            $period = $this->periodToDate($request->validated('period_month'));
            $supplierId = (int) $request->validated('supplier_rm_id');

            $existing = Forecast::query()
                ->where('supplier_rm_id', $supplierId)
                ->whereDate('period_month', $period)
                ->when($forecast->exists, fn ($q) => $q->whereKeyNot($forecast->id))
                ->first();

            if ($existing) {
                $forecast = $existing;
            }

            $filePath = $forecast->file_path;
            $original = $forecast->original_filename;

            if ($request->hasFile('file')) {
                if ($filePath) {
                    Storage::disk('public')->delete($filePath);
                }
                $filePath = $request->file('file')->store('forecasts', 'public');
                $original = $request->file('file')->getClientOriginalName();
            }

            $forecast->fill([
                'supplier_rm_id' => $supplierId,
                'period_month' => $period,
                'notes' => $request->validated('notes'),
                'file_path' => $filePath,
                'original_filename' => $original,
                'uploaded_by' => $request->user()->id,
            ])->save();

            $itemRows = collect($request->input('items', []))
                ->filter(fn ($row) => filled($row['item_id'] ?? null) && (int) ($row['qty'] ?? 0) > 0)
                ->unique('item_id')
                ->values();

            if ($itemRows->isNotEmpty() || $request->has('items')) {
                $catalog = Item::query()
                    ->whereIn('id', $itemRows->pluck('item_id'))
                    ->get()
                    ->keyBy('id');

                $forecast->items()->delete();

                foreach ($itemRows as $row) {
                    $item = $catalog->get((int) $row['item_id']);
                    if (! $item) {
                        continue;
                    }

                    $forecast->items()->create([
                        'item_id' => $item->id,
                        'item_number' => $item->item_number,
                        'description' => $item->description,
                        'qty' => (int) $row['qty'],
                    ]);
                }
            }

            return $forecast->fresh(['items', 'supplierRm']);
        });
    }

    private function periodToDate(string $period): Carbon
    {
        return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
    }
}
