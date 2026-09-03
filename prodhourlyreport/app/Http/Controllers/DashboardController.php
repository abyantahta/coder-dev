<?php

namespace App\Http\Controllers;

use App\Exports\ProductionLogsExport;
use App\Models\Line;
use App\Models\ProductionLog;
use App\Models\User;
use App\Support\ProductCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $this->filters($request);

        $lines = $user->accessesAllLines()
            ? Line::query()->orderBy('name')->get(['id', 'name'])
            : $user->lines()->orderBy('lines.name')->get(['lines.id', 'lines.name']);

        $logsQuery = $this->scopedQuery($user, $filters)
            ->with(['line:id,name', 'productModel:id,name', 'product:id,name,category', 'user:id,name'])
            ->orderByDesc('logged_at');

        $logs = (clone $logsQuery)
            ->paginate(50)
            ->withQueryString();

        $chartData = [];
        $selectedProductName = null;
        if ($filters['product_id']) {
            $selectedProductName = \App\Models\Product::query()
                ->whereKey($filters['product_id'])
                ->value('name');

            $chartData = $this->scopedQuery($user, $filters)
                ->orderBy('logged_at')
                ->get(['logged_at', 'total_production', 'total_reject', 'total_repair'])
                ->map(fn ($log) => [
                    'timeLabel' => $log->logged_at->timezone(config('app.timezone'))->format('H:i'),
                    'total_production' => $log->total_production,
                    'total_repair' => $log->total_repair,
                    'total_reject' => $log->total_reject,
                ])->values()->all();
        }

        // Totals: latest checkpoint per product in the filtered day scope (uncapped).
        $allForTotals = $this->scopedQuery($user, $filters)
            ->orderBy('logged_at')
            ->get(['product_id', 'logged_at', 'total_production', 'total_reject', 'total_repair']);

        $latestPerProduct = [];
        foreach ($allForTotals as $log) {
            $existing = $latestPerProduct[$log->product_id] ?? null;
            if (! $existing || $log->logged_at->gt($existing->logged_at)) {
                $latestPerProduct[$log->product_id] = $log;
            }
        }
        $latestRows = array_values($latestPerProduct);
        $totals = [
            'production' => array_sum(array_map(fn ($l) => $l->total_production, $latestRows)),
            'reject' => array_sum(array_map(fn ($l) => $l->total_reject, $latestRows)),
            'repair' => array_sum(array_map(fn ($l) => $l->total_repair, $latestRows)),
            'entries' => $allForTotals->count(),
        ];

        return Inertia::render('Dashboard', [
            'filters' => $filters,
            'lines' => $lines,
            'logs' => $logs,
            'chartData' => $chartData,
            'selectedProductName' => $selectedProductName,
            'totals' => $totals,
            'categories' => ProductCategories::options(),
        ]);
    }

    public function export(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $this->filters($request);

        $query = $this->scopedQuery($user, $filters)
            ->with(['line:id,name', 'productModel:id,name', 'product:id,name,category', 'user:id,name'])
            ->orderBy('logged_at');

        return Excel::download(
            new ProductionLogsExport($query),
            "production-log-{$filters['date']}.xlsx"
        );
    }

    /**
     * @return array{date: string, line_id: ?int, product_model_id: ?int, product_id: ?int, category: ?string, search: ?string, page: int}
     */
    private function filters(Request $request): array
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');

        return [
            'date' => $date,
            'line_id' => $request->integer('line_id') ?: null,
            'product_model_id' => $request->integer('product_model_id') ?: null,
            'product_id' => $request->integer('product_id') ?: null,
            'category' => $request->string('category')->trim()->toString() ?: null,
            'search' => $request->string('search')->trim()->toString() ?: null,
            'page' => max(1, $request->integer('page') ?: 1),
        ];
    }

    private function scopedQuery(User $user, array $filters)
    {
        $visibleLineIds = $user->visibleLineIds();
        $dayStart = $filters['date'].' 00:00:00';
        $dayEnd = $filters['date'].' 23:59:59';

        return ProductionLog::query()
            ->whereBetween('logged_at', [$dayStart, $dayEnd])
            ->when($visibleLineIds !== null, fn ($query) => $query->whereIn('line_id', $visibleLineIds))
            ->when($filters['line_id'], fn ($query, $lineId) => $query->where('line_id', $lineId))
            ->when($filters['product_model_id'], fn ($query, $id) => $query->where('product_model_id', $id))
            ->when($filters['product_id'], fn ($query, $id) => $query->where('product_id', $id))
            ->when($filters['category'], function ($query, string $category) {
                $query->whereHas('product', fn ($q) => $q->where('category', $category));
            })
            ->when($filters['search'], function ($query, string $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->whereHas('product', function ($product) use ($like) {
                        $product->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like)
                            ->orWhere('part_number', 'like', $like);
                    })
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like))
                        ->orWhereHas('line', fn ($line) => $line->where('name', 'like', $like))
                        ->orWhereHas('productModel', fn ($model) => $model->where('name', 'like', $like));
                });
            });
    }
}
