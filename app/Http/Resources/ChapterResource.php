<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'novel_id' => (int) $this->novel_id,
            'chapter_number' => $this->chapter_number,
            'title' => $this->title,
            'audio_url' => $this->audio_url,
            'content_text' => $this->content_text,
            'order_index' => $this->order_index,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}