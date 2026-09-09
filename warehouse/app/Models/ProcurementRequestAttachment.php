<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementRequestAttachment extends Model
{
    protected $fillable = ['request_id', 'file_path', 'original_name', 'mime_type', 'size', 'uploaded_by'];

    public function request()
    {
        return $this->belongsTo(ProcurementRequest::class, 'request_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getIconClass(): string
    {
        return match(true) {
            str_contains($this->mime_type ?? '', 'pdf')   => 'bi-file-earmark-pdf text-danger',
            str_contains($this->mime_type ?? '', 'sheet'), str_contains($this->mime_type ?? '', 'excel') => 'bi-file-earmark-excel text-success',
            str_contains($this->mime_type ?? '', 'image')  => 'bi-file-earmark-image text-primary',
            default => 'bi-file-earmark text-muted',
        };
    }

    public function getFormattedSize(): string
    {
        $kb = $this->size / 1024;
        return $kb > 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 0) . ' KB';
    }
}
