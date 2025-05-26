<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->profile ? $this->profile->username : explode('@', $this->email)[0],
            'role' => $this->profile ? $this->profile->role : 'user',
            'created_at' => $this->created_at->toISOString(),
            'last_sign_in_at' => $this->profile && $this->profile->last_sign_in_at
                ? $this->profile->last_sign_in_at->toISOString()
                : null,
        ];
    }
}