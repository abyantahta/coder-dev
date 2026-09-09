<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemDepartmentBudget;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index()
    {
        $stats = [
            'pending_approvals'  => Transaction::where('status', 'pending')->where('trans_type', '!=', 'goods_out')->count(),
            'today_transactions' => Transaction::whereDate('created_at', today())->count(),
            'total_items'        => Item::where('is_active', true)->count(),
            'low_stock_items'    => Item::with(['departmentStocks', 'memoStock', 'minimumStock'])
                ->whereHas('minimumStock', fn($q) => $q->where('is_active', true))
                ->get()
                ->filter(fn($item) => $item->isBelowMinimum())
                ->count(),
        ];

        $recentTransactions = Transaction::with(['user', 'department', 'details'])
            ->latest()->limit(8)->get();

        // Chart data
        $monthlyData = Transaction::select(
                DB::raw('MONTH(created_at) as month'),
                'trans_type',
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('created_at', now()->year)
            ->groupBy('month', 'trans_type')
            ->orderBy('month')
            ->get();

        $chartLabels = collect(range(1, 12))->map(fn($m) => now()->setMonth($m)->format('M'));
        $chartOut    = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_out')->where('month', $m)->sum('total'));
        $chartIn     = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_in')->where('month', $m)->sum('total'));
        $chartReturn = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_return')->where('month', $m)->sum('total'));

        // ===== DATA HARGA (SUPERADMIN ONLY) =====
        $priceStats = null;
        if (auth()->user()->isSuperAdmin()) {
            $priceStats = $this->getPriceStats();
        }

        // ===== BUDGET VS ACTUAL 12 BULAN (departemen user yang login) =====
        $budgetHistory = [];
        $deptId = auth()->user()->department_id;
        if ($deptId) {
            $itemIds = ItemDepartmentBudget::where('department_id', $deptId)->pluck('procurement_item_id');
            if ($itemIds->isNotEmpty()) {
                for ($i = 11; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $budget = 0;
                    $consumed = 0;
                    foreach ($itemIds as $itemId) {
                        $budget   += $this->budgetService->effectiveBudget($deptId, $itemId, $date->year, $date->month);
                        $consumed += $this->budgetService->consumed($deptId, $itemId, $date->year, $date->month);
                    }
                    $budgetHistory[] = ['label' => $date->translatedFormat('M Y'), 'budget' => $budget, 'consumed' => $consumed];
                }
            }
        }

        // Bulan berjalan = entri terakhir di $budgetHistory (loop di atas jalan dari 11 bulan lalu -> bulan ini).
        $currentMonthBudget   = $budgetHistory ? end($budgetHistory)['budget'] : 0;
        $currentMonthConsumed = $budgetHistory ? end($budgetHistory)['consumed'] : 0;

        return view('dashboard.index', compact(
            'stats', 'recentTransactions',
            'chartLabels', 'chartOut', 'chartIn', 'chartReturn',
            'priceStats', 'budgetHistory', 'currentMonthBudget', 'currentMonthConsumed'
        ));
    }

    private function getPriceStats(): array
    {
        $items = Item::with(['departmentStocks', 'memoStock'])->where('is_active', true)->get();

        // Total nilai inventory
        $totalInventoryValue = $items->sum(fn($item) => $item->getInventoryValue());

        // Nilai keluar barang bulan ini
        $thisMonth = now()->startOfMonth();
        $goodsOutValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_out')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Nilai pengembalian bulan ini
        $returnValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_return')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Nilai masuk barang bulan ini
        $goodsInValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_in')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Top 5 item nilai inventory tertinggi
        $topItems = $items->map(fn($item) => [
            'description' => $item->description,
            'item_code'   => $item->item_code,
            'qty'         => $item->getQtyOnHand(),
            'unit'        => $item->unit,
            'price'       => $item->price,
            'value'       => $item->getInventoryValue(),
        ])->sortByDesc('value')->take(5)->values();

        // Chart nilai transaksi per bulan (12 bulan)
        $valueChartData = collect(range(1, 12))->map(function ($month) {
            $out = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->join('items', 'transaction_details.item_id', '=', 'items.id')
                ->where('transactions.trans_type', 'goods_out')
                ->where('transactions.status', 'completed')
                ->whereYear('transactions.trans_date', now()->year)
                ->whereMonth('transactions.trans_date', $month)
                ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
                ->value('total') ?? 0;

            $in = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->join('items', 'transaction_details.item_id', '=', 'items.id')
                ->where('transactions.trans_type', 'goods_in')
                ->where('transactions.status', 'completed')
                ->whereYear('transactions.trans_date', now()->year)
                ->whereMonth('transactions.trans_date', $month)
                ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
                ->value('total') ?? 0;

            return ['out' => (float) $out, 'in' => (float) $in];
        });

        return [
            'total_inventory_value' => $totalInventoryValue,
            'goods_out_value'       => $goodsOutValue,
            'goods_in_value'        => $goodsInValue,
            'return_value'          => $returnValue,
            'top_items'             => $topItems,
            'value_chart_out'       => $valueChartData->pluck('out')->values(),
            'value_chart_in'        => $valueChartData->pluck('in')->values(),
            'items_with_price'      => $items->where('price', '>', 0)->count(),
            'items_no_price'        => $items->where('price', 0)->count(),
        ];
    }
}
