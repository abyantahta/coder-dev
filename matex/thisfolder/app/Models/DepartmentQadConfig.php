<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentQadConfig extends Model
{
    protected $fillable = [
        'department_id', 'site_code', 'buyer_code', 'approver_code', 'end_user_id', 'requester_userid',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
