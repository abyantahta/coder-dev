<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\UserOnTransactionResource;
use App\Http\Resources\ItemResource;
use Illuminate\Support\Facades\Storage;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kondisi' => $this->kondisi,
            'image_path' =>
            $this->image_path && !(str_starts_with($this->image_path, 'http')) ?
            Storage::url($this->image_path) : '',
            'created_by' => new UserResource($this->createdBy),
            'pic' => new UserResource($this->userPIC),
            'updated_by' => new UserResource($this->updatedBy),
            'keterangan' => $this->keterangan,
            'cutoff_counter' => $this->cutoff_counter,
            'isEditable' => $this->isEditable,
            'item_id' => new ItemResource($this->item),
            'location_id' => new LocationResource($this->location),
            'lokasi' => $this->lokasi,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
