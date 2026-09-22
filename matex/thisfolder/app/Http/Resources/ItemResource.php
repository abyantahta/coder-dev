<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'no_asset'=> $this->no_asset,
            'encrypted_no_asset'=> $this->encrypted_no_asset,
            'name'=> $this->name,
            'category_id'=> new CategoryResource($this->category),
            'service_date'=> $this->service_date,
            'disposal_date'=> $this->disposal_date,
            'cost'=> $this->cost,
            'nbv'=> $this->nbv,
            'lokasi'=> $this->lokasi,
            'isNew'=> $this->isNew,
            'isSTO'=> $this->isSTO,
        ];
    }
}
