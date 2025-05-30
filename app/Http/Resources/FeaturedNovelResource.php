<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedNovelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'novel_id' => (int) $this->novel_id,
            'position' => $this->position,
            'start_date' => $this->start_date->toIso8601String(),
            'end_date' => $this->end_date->toIso8601String(),
            'novel' => $this->novel ? [
                'id' => (int) $this->novel->id,
                'title' => $this->novel->title,
                'author_id' => (int) $this->novel->author_id,
                'author' => $this->novel->author ? [
                    'id' => (int) $this->novel->author->id,
                    'name' => $this->novel->author->name,
                ] : null,
                'publisher' => $this->novel->publisher,
                'cover_image_url' => $this->novel->cover_image_url ? asset($this->novel->cover_image_url):null,
                'synopsis' => $this->novel->synopsis,
                'status' => $this->novel->status,
                'publishing_year' => $this->novel->publishing_year,
                'created_at' => $this->novel->created_at->toIso8601String(),
                'updated_at' => $this->novel->updated_at->toIso8601String(),
            ] : null,
        ];
    }
}