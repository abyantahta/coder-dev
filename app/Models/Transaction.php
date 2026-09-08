<?php

namespace App\Models;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    protected $guarded = [];
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, LogsActivity;
    protected $with = ['item','createdBy','updatedBy'];
    protected $logName = 'sto_transactions';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['item_id','lokasi','keterangan','pic','kondisi']);
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }    
    public function userPIC()
    {
        return $this->belongsTo(User::class, 'pic');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    public function cutoff()
    {
        return $this->belongsTo(CutoffHistory::class, 'cutoff_counter');
    }
}
