<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function departmentStocks()
    {
        return $this->hasMany(DepartmentStock::class);
    }

    public function qadConfig()
    {
        return $this->hasOne(DepartmentQadConfig::class);
    }
}
