<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'trans_no', 'trans_type', 'user_id', 'requester_id', 'department_id',
        'status', 'approved_by', 'approved_at', 'notes',
        'reject_reason', 'qad_receipt_no', 'trans_date',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'trans_date'  => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function getTypeLabel(): string
    {
        return match($this->trans_type) {
            'goods_out'    => 'Keluar Barang',
            'goods_in'     => 'Masuk Barang',
            'memo_in'      => 'Memo Masuk',
            'memo_out'     => 'Memo Keluar',
            'goods_return' => 'Pengembalian Barang',
            'ga_checkout'  => 'Serah Terima GA',
            default        => $this->trans_type,
        };
    }

    public function getTypeBadgeColor(): string
    {
        return match($this->trans_type) {
            'goods_out'    => 'danger',
            'goods_in'     => 'success',
            'memo_in'      => 'info',
            'memo_out'     => 'warning',
            'goods_return' => 'primary',
            'ga_checkout'  => 'dark',
            default        => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'   => 'Menunggu',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            'completed' => 'Selesai',
            default     => $this->status,
        };
    }

    public function getStatusBadge(): string
    {
        return match($this->status) {
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'completed' => 'info',
            default     => 'secondary',
        };
    }

    public static function generateTransNo(string $type): string
    {
        $prefix = match($type) {
            'goods_out'    => 'OUT',
            'goods_in'     => 'IN',
            'memo_in'      => 'MIN',
            'memo_out'     => 'MOT',
            'goods_return' => 'RET',
            'ga_checkout'  => 'GAC',
            default        => 'TRX',
        };
        $date = now()->format('Ymd');
        $last = self::where('trans_type', $type)
            ->whereDate('created_at', today())
            ->count() + 1;
        return $prefix . $date . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
