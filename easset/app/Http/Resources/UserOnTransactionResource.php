<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserOnTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->item_id,
            'no_asset' => $this->no_asset,
            'name' => $this->name,
            'category_id' => new CategoryResource($this->category),
            'lokasi' => $this->lokasi,
        ];
    }
}
