<?php

namespace App\Http\Controllers;

use App\Services\WsaService;
use App\Services\WsaCategoryResolver;
use App\Services\DepreciationScheduler;
use Illuminate\Http\Request;
use Exception;
use App\Models\Category;
use App\Models\Depreciation;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

set_time_limit(900);

class AssetController extends Controller
{
    public function store(Request $request)
    {
        $loadingToggle = request("loadingToggle");
            $itemwsa = (new WsaService())->wsaasset();

        if (($itemwsa === false)) {
            return redirect()->back()->with('success', [
                "toggle" => !$loadingToggle,
                "status" => "error",
                "message" => "Can't load data, WSA Problem"
            ]);
        } else {
            $categoryResolver = new WsaCategoryResolver();
            $depreciationScheduler = new DepreciationScheduler();
            DB::begintransaction();
            try {
                foreach ($itemwsa[0] as $datas) {
                    $items = Item::where('no_asset',$datas->t_fa_id)->first();
                    if($items === null){
                        $this->createItemFromWsa($datas, $categoryResolver, $depreciationScheduler);
                    }
                    else{
                        $this->updateItemFromWsa($items, $datas);
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                Log::error('WSA sync: failed while processing synced items', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                return redirect()->back()->with('success', [!$loadingToggle,"Items failed to load"]);
            }
        }
        return redirect()->back()->with('success', [
            "toggle" => !$loadingToggle,
            "status" => "success",
            "message" => "Items successfully loaded"
        ]);
    }

	private function createItemFromWsa($datas, WsaCategoryResolver $categoryResolver, DepreciationScheduler $depreciationScheduler)
	{
		$items = new Item(['no_asset' => $datas->t_fa_id]);
		$items->name  = $datas->t_fa_desc1;
		$items->cost  = $datas->t_fa_puramt;
		$items->depreciation  = $datas->t_fabd_accamt;
		$items->nbv = ($datas->t_fa_puramt - $datas->t_fabd_accamt);
		$items->disposal_date  = ($datas->t_fa_disp_dt == "") ?  null : Carbon::parse($datas->t_fa_disp_dt);
		$items->service_date  = Carbon::parse($datas->t_fa_startdt);
		$items->encrypted_no_asset = $this->handleHashing($datas->t_fa_id);
		$items->lokasi  = $datas->t_fa_faloc_id;

		$resolved = $categoryResolver->resolve($datas->t_fa_facls_id);
		$category = Category::where('name', $resolved['categoryName'])->first();
		$lifetime = $resolved['lifetimeOverride'] ?? $category->lifetime;
		$items->category_id = $category->id;
		$items->depreciation_per_month = $datas->t_fa_puramt / $lifetime;

		$items->save();

		$schedule = $depreciationScheduler->buildSchedule(
			(float) $items->cost,
			(float) $items->depreciation_per_month,
			Carbon::parse($datas->t_fa_startdt),
			$items->disposal_date,
			Carbon::now()
		);

		foreach ($schedule as $row) {
			Depreciation::create([
				'item_id' => $items->id,
				'category_id' => $items->category_id,
				'month' => $row['month'],
				'year' => $row['year'],
				'depreciation' => $row['depreciation'],
				'nbv' => $row['nbv'],
				'depreciation_per_month' => $row['depreciation_per_month'],
			]);
		}

		if ($items->disposal_date && !empty($schedule)) {
			$items->nbv = floor($items->cost - end($schedule)['depreciation']);
			$items->save();
		}
	}

	private function updateItemFromWsa(Item $items, $datas)
	{
		$items->depreciation  = $datas->t_fabd_accamt;
		$items->nbv  = ($datas->t_fa_puramt - $datas->t_fabd_accamt);
		$items->disposal_date  = ($datas->t_fa_disp_dt == "") ?  null : Carbon::parse($datas->t_fa_disp_dt);
		$isDepreciationExist = Depreciation::where('item_id',$items->id)->where('month',Carbon::now()->month)->where('year', Carbon::now()->year)->first();
		if(!$isDepreciationExist && floor($items->nbv)!=0 && $items->disposal_date == null ){
			Depreciation::create([
				'item_id'=> $items->id,
				'category_id' => $items->category_id,
				'month' => Carbon::now()->month,
				'year' => Carbon::now()->year,
				'depreciation' => $items->depreciation,
				'nbv' => $items->nbv,
				'depreciation_per_month' => $items->depreciation_per_month
			]);
		}
		$items->save();
	}

	private function handleHashing($plaintext){
    do {
        $hashed = Hash::make($plaintext);
    } while (Str::contains($hashed, '/') || Str::contains($hashed, '_'));

    return $hashed;
	}
}
