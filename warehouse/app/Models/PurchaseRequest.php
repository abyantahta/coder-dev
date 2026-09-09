<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'pr_no', 'source', 'department_id', 'status', 'notes', 'created_by', 'submitted_at',
        'director_by', 'director_at', 'director_notes', 'reject_reason',
        'qad_req_no', 'qad_po_no', 'sent_to_qad_at', 'qad_sync_message',
        'received_by', 'received_at', 'distributed_at',
    ];

    protected $casts = [
        'submitted_at'   => 'datetime',
        'director_at'    => 'datetime',
        'sent_to_qad_at' => 'datetime',
        'received_at'    => 'datetime',
        'distributed_at' => 'datetime',
    ];

    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function directorBy() { return $this->belongsTo(User::class, 'director_by'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function details()    { return $this->hasMany(PurchaseRequestDetail::class, 'pr_id'); }

    public function sourceRequests()
    {
        return $this->hasMany(ProcurementRequest::class, 'aggregated_into_pr_id');
    }

    /**
     * User departemen yang aslinya minta barang ini (bukan staff GA yang
     * mengagregasi/mengirim ke QAD) — dipakai QadSoapService buat resolve
     * field routing per-user (qad_route_to_apr dll) di header requisition.
     */
    public function requesterUser(): ?User
    {
        return $this->sourceRequests()->with('user')->first()?->user;
    }

    /** Kode buyer QAD dipakai saat approve (field Route To/Buyer) — dari requester, fallback config departemen. */
    public function qadBuyerCode(): ?string
    {
        $user = $this->requesterUser();
        if ($user && $user->qad_route_to_buyer) {
            return $user->qad_route_to_buyer;
        }
        return DepartmentQadConfig::where('department_id', $this->department_id)->value('buyer_code');
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft'              => 'Draft',
            'submitted'          => 'Submitted',
            'sent_to_qad'        => 'Terkirim ke QAD',
            'send_failed'        => 'Gagal Kirim ke QAD',
            'director_confirmed'  => 'Disetujui Direktur',
            'director_denied'     => 'Ditolak Direktur',
            'received'            => 'Barang Diterima',
            'partially_received'  => 'Diterima Sebagian',
            'distributing'        => 'Sedang Didistribusikan',
            'completed'          => 'Selesai',
            'cancelled'          => 'Dibatalkan',
            default              => $this->status,
        };
    }

    public function getStatusBadge(): string
    {
        return match($this->status) {
            'draft'              => 'secondary',
            'submitted'          => 'primary',
            'sent_to_qad'        => 'primary',
            'send_failed'        => 'danger',
            'director_confirmed'  => 'success',
            'director_denied'     => 'danger',
            'received'            => 'info',
            'partially_received'  => 'warning',
            'distributing'        => 'info',
            'completed'          => 'success',
            'cancelled'          => 'danger',
            default              => 'secondary',
        };
    }

    public static function generatePRNo(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->count() + 1;
        return 'PR' . $date . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
