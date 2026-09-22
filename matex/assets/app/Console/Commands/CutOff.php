<?php

namespace App\Console\Commands;

use App\Models\CutoffHistory;
use App\Models\Item;
use App\Models\Transaction;
use App\Support\StoProgress;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CutOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cut-off';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = CutoffHistory::all()->last();
        
        $cutoff_counter = ($cutoff) ? ($cutoff->cutoff_counter) : 0;
        if($cutoff){
            $totalActiveItems = Item::where('disposal_date',null)->where('isNew',false)->count();
            $sto_count = Item::where('disposal_date',null)->where('isNew',false)->where('isSTO',true)->count();
            $sto_progress = StoProgress::ratio($sto_count, $totalActiveItems);

            $cutoff->sto_progress = $sto_progress;
            $cutoff->save();
        }
        $transactions = Transaction::where('cutoff_counter',$cutoff_counter)->get();
        foreach ($transactions as $transaction){
            $transaction->isEditable = false;
            $transaction->save();
    }

        CutoffHistory::create([
            'cutoff_counter' => $cutoff_counter+1,
            'cutoff_date' => Carbon::now(),
            'sto_progress'=> 0
        ]);
        $items = Item::all();
        
        foreach ($items as $item){
            $item->isSTO = false;
            $item->isNew = false;
            $item->save();
        }
    }
}
