<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name'=> $this->name,
            'email'=> $this->email,
            'role_id'=> $this->role_id,
            'department_id'=> $this->department_id,
            'jabatan_id'=> $this->jabatan_id,
            'role'=> $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ]),
            'department'=> $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'jabatan'=> $this->whenLoaded('jabatan', fn () => $this->jabatan ? [
                'id' => $this->jabatan->id,
                'name' => $this->jabatan->name,
            ] : null),
        ];
    }
}
