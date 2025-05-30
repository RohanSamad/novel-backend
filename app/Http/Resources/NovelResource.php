<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NovelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'author_id' => (int) $this->author_id,
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => (int) $this->author->id,
                'name' => $this->author->name,
            ] : null),
            'publisher' => $this->publisher,
            'cover_image_url' => $this->cover_image_url ? asset( $this->cover_image_url):null,
            'synopsis' => $this->synopsis,
            'status' => $this->status,
            'publishing_year' => $this->publishing_year,
            'genres' => $this->whenLoaded('genres', fn () => $this->genres->map(function ($genre) {
                return [
                    'id' => (int) $genre->id,
                    'name' => $genre->name,
                    'slug' => $genre->slug,
                ];
            })->toArray()),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}