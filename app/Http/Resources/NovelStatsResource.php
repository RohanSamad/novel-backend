<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NovelStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'chapter_count' => (int) $this->chapter_count,
            'reader_count' => (int) $this->reader_count,
            'average_rating' => round((float) $this->average_rating, 1),
            'rating_count' => (int) $this->rating_count,
            'total_views' => (int) $this->total_views,
            'last_updated' => $this->last_updated ? $this->last_updated->toIso8601String() : null,
        ];
    }
}