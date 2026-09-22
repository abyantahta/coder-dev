<?php

namespace App\Http\Controllers;

use App\Exports\ExportItemsForDepartment;
use App\Exports\ExportUrl;
use App\Imports\ImportItemDepartments;
use App\Models\Item;
use App\Http\Resources\ItemResource;
use App\Http\Resources\TransactionResource;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Item::query();
        $sortField = request("sort_field", "created_at");
        $sortDirection = request("sort_direction", "desc");

        if (request("no_asset")) {
            $query->where("no_asset", "like", "%" . request("no_asset") . "%");
        }
        if (request("category_id")) {
            $query->where('category_id', request("category_id"));
        }
        if (request("isDisposal")==1) {
            $query->whereNull('disposal_date');
        }else if(request("isDisposal")==2){
            $query->whereNotNull('disposal_date');
        }
        if (request("sto_status")==1) {
            $query->where('isSTO', true);
        }else if(request("sto_status")==2){
            $query->where('isSTO', false)->where('isNew',false)->whereNull('disposal_date');
        }else if(request("sto_status")==3){
            $query->where('isSTO', false)->where('isNew',true)->whereNull('disposal_date');
        }
        $items = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $categories = Category::all();
        return inertia("Items/Index",[
            "items" => ItemResource::collection($items),
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
            "categories"=> $categories,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($encryptedId)
    {
    	$output = explode('_',$encryptedId);
    	$key = $output[0];
    	$no_asset = $output[1];
    	
    try {
    	if (!(Hash::check($no_asset, $key))) {
        	abort(404);
		}
    } catch (\Exception $e) {
        abort(404);
    }
    
        $item = Item::query()->where('no_asset',$no_asset)->get();
        $transactions = Transaction::query()->where('item_id', $item[0]->id)->get();
        return inertia("Items/Show", [
            "item" => ItemResource::collection($item),
            "transactions" => TransactionResource::collection($transactions),
            "canSto" => $item[0]->isEligibleForSto() && (request()->user()?->hasPermission('perform-sto') ?? false),
        ]);
    }

    public function exportUrl(){
        return Excel::download(new ExportUrl(), 'items.xlsx');
    }

    public function departmentsPage()
    {
        return inertia("ItemDepartments/Index", [
            "totalItems" => Item::count(),
            "itemsWithoutDepartment" => Item::whereNull('department_id')->count(),
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function exportDepartments()
    {
        return Excel::download(new ExportItemsForDepartment(), 'items-department.xlsx');
    }

    public function importDepartments(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new ImportItemDepartments();
        Excel::import($import, $request->file('file'));

        $message = "{$import->updated} item(s) updated.";
        if ($import->blank > 0) {
            $message .= " {$import->blank} row(s) left blank (not filled in yet).";
        }
        if (!empty($import->skipped)) {
            $shown = array_slice($import->skipped, 0, 20);
            $message .= ' Issues: ' . implode('; ', $shown);
            if (count($import->skipped) > 20) {
                $message .= ' ... and ' . (count($import->skipped) - 20) . ' more.';
            }
        }

        return to_route('items.departments')->with('success', $message);
    }
}
