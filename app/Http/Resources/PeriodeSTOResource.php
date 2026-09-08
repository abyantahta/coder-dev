<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodeSTOResource extends JsonResource
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
            'cutoff_counter'=> $this->cutoff_counter,
            'start_period'=> Carbon::parse($this->cutoff_date)->format('M Y'),
            'end_period'=> Carbon::parse($this->cutoff_date)->addYear()->format('M Y'),
        ];
    }
}
