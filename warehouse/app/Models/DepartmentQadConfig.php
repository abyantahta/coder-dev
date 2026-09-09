<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentQadConfig extends Model
{
    protected $fillable = [
        'department_id', 'site_code', 'buyer_code', 'approver_code', 'end_user_id', 'requester_userid',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
