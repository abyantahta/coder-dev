<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Depreciation;
use App\Models\Item;
use App\Support\StoProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;

class DashboardController extends Controller
{
    public function index()
    {
        $itemsQuery = Item::query();
        $activeItemsQuery = (clone $itemsQuery);
        $years = Item::select(
            FacadesDB::raw("(DATE_FORMAT(service_date, '%Y')) as year"),
            )
            ->groupBy("year")->get();
            $year_name = collect(json_decode($years))->pluck('year');

            $activeItemsQuery->whereDate('service_date','<',request('years')?Carbon::parse('31-12-'.request('years'))->endOfYear():Carbon::parse('31-12-'.Carbon::now()->year)->endOfYear())
                ->where(function($query){
                    $query->where('disposal_date','>=',request('years')? Carbon::parse('31-12-'.request('years'))->endOfYear():Carbon::parse('31-12-'.Carbon::now()->year)->endOfYear())
                    ->orWhereNull('disposal_date');
                });
            
            
        $deactiveItemsQuery = (clone $itemsQuery)->whereNotNull('disposal_date');
        $depreciationByMonth = Depreciation::query()->where('year', request('years') ?: Carbon::now()->year);
        $items = Item::select("no_asset", "service_date", "disposal_date", "nbv", "categories.name AS category")->leftJoin('categories', 'categories.id', '=', 'items.category_id')->whereYear('service_date',request("years")?: Carbon::now()->year);
        $items_for_disposal = clone $items;
        if (request("years")) {
            $years = request("years");
            $items = $items->whereYear('service_date',$years);
            $items_for_disposal->whereYear('disposal_date',$years);
            $itemsQuery->whereDate('service_date','<',Carbon::parse('31-12-'.request('years'))->endOfYear());
            
            $deactiveItemsQuery->whereDate('disposal_date','<=',Carbon::parse('31-12-'.request('years')));
    }
        if (request("category_id")) {
            $category_id = request("category_id");
            $items = $items->where("category_id",$category_id);
            $items_for_disposal->where("category_id",$category_id);
            $itemsQuery = $itemsQuery->where("category_id",$category_id);
            $depreciationByMonth = $depreciationByMonth->where("category_id",$category_id);
        }
        
        $categories = Category::select("name")->get();
        $items_number = (clone $itemsQuery)->count();
        $activeItems_query = (clone $activeItemsQuery);
        $deactiveItems_query = (clone $deactiveItemsQuery);

        $activeItems = (clone $activeItems_query)->count();
        $deactiveItems = (clone $deactiveItems_query)->count();
        //TOTAL NBV
        $totalNBVAssets = (clone $items)->whereNull('disposal_date')->sum('nbv');

        //DEPRESIASI
        $depreciation = floor((clone $activeItems_query)->sum('depreciation'));
        
        //DEPRESIASI PER BULAN
        $depreciation_per_month = (clone $items)->sum('depreciation_per_month');



        $months_label = ["JAN", "FEB", "MAR", "APR", "MEI", "JUNI", "JULI", "AUG", "SEP", "OKT", "NOV", "DES"];

        //PENAMBAHAN ASET MONTHLY
        $penambahan_aset_monthly = $this->monthlyTotals(
            $items,
            fn ($query, $month) => $query->whereMonth('service_date', $month),
            'cost',
            1000000000
        );

        //DISPOSAL ASET MONTHLY
        $disposal_aset_monthly = $this->monthlyTotals(
            $items_for_disposal,
            fn ($query, $month) => $query->whereMonth('disposal_date', $month),
            'nbv',
            1000000
        );

        //DEPRESIASI ASET MONTHLY
        $depreciation_aset_monthly = $this->monthlyTotals(
            $depreciationByMonth,
            fn ($query, $month) => $query->where('month', $month),
            'depreciations.depreciation_per_month',
            1000000000
        );

        $depreciationByMonths = [
            'label'=> $months_label,
            'data' => $depreciation_aset_monthly
        ];

        //PRESENTASE JENIS ASET AKTIF
        $categoryLabels = [$categories[0]->name, $categories[1]->name, $categories[2]->name, $categories[3]->name, $categories[4]->name];
        $itemsByCategories = [
            "label" => $categoryLabels,
            "data" => $this->perCategory($activeItems_query),
        ];

        $costPerCategory = $this->perCategory($activeItems_query, 'cost');
        $nbvPerCategory = $this->perCategory($activeItems_query, 'nbv');
        $costPerCategories = [
            "label" => $categoryLabels,
            "data" => $costPerCategory,
        ];

        $nbv_cost_category = [];
        foreach ($categoryLabels as $index => $categoryLabel) {
            $nbv_cost_category[] = [
                "name" => $categoryLabel,
                "data" => [$costPerCategory[$index], $nbvPerCategory[$index]],
            ];
        }

        $totalActiveItems = Item::where('disposal_date',null)->where('isNew',false)->count();
        $sto_count = Item::where('disposal_date',null)->where('isNew',false)->where('isSTO',true)->count();
        $sto_progress = (int) ceil(StoProgress::ratio($sto_count, $totalActiveItems) * 100);
        

        return inertia("Dashboard", [
            "years"=> $year_name,
            "numberOfItems" => $items_number,
            "numberOfActiveItems" => $activeItems,
            "numberOfDeactiveItems" => $deactiveItems,
            "itemsByCategories" => $itemsByCategories,
            "totalNBVAssets" => $totalNBVAssets,
            "penambahan_aset_monthly" => $penambahan_aset_monthly,
            "disposal_aset_monthly" => $disposal_aset_monthly,
            "months_label" => $months_label,
            "cost_per_categories" => $costPerCategories,
            "nbv_cost_category" => $nbv_cost_category,
            "depreciation"=> $depreciation,
            "depreciation_per_month"=> $depreciation_per_month,
            "depreciationByMonths"=> $depreciationByMonths,
            "queryParams" => request()->query() ?: null,
            "sto_progress" => $sto_progress,
        ]);
    }

    /**
     * Sum `$sumColumn` on `$baseQuery` for each of the 12 months, scoped by
     * `$scopeToMonth`, expressed in units of `$divisor` and rounded to 2
     * decimal places.
     *
     * @return array<int, float> 12 values, January first
     */
    private function monthlyTotals($baseQuery, \Closure $scopeToMonth, string $sumColumn, float $divisor): array
    {
        $totals = [];
        for ($month = 1; $month <= 12; $month++) {
            $sum = $scopeToMonth(clone $baseQuery, $month)->sum($sumColumn);
            $totals[] = ceil(($sum / $divisor) * 100) / 100;
        }

        return $totals;
    }

    /**
     * Count (or sum `$sumColumn`) on `$baseQuery` for each of the 5 seeded
     * categories (id 1-5), in that order.
     *
     * @return array<int, int|float>
     */
    private function perCategory($baseQuery, ?string $sumColumn = null): array
    {
        $results = [];
        for ($categoryId = 1; $categoryId <= 5; $categoryId++) {
            $query = (clone $baseQuery)->where('category_id', (string) $categoryId);
            $results[] = $sumColumn ? $query->sum($sumColumn) : $query->count();
        }

        return $results;
    }
}
