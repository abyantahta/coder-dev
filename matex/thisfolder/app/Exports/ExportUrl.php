<?php

namespace App\Exports;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class ExportUrl implements FromView
{

    public function view(): View
    {
        $query = Item::query()->get();
        return view('exportUrl', [
            "items" => $query,
        ]);
    }
}
