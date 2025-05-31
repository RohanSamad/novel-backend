<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NovelRatingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'novel_id' => (int) $this->novel_id,
            'user_id' => (int) $this->user_id,
            'rating' => (int) $this->rating,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}