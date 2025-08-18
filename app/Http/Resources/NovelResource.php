<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NovelResource extends JsonResource
{
    protected $shortQuery;
    protected $totalChapters;
    protected $latestChapter;

    public function __construct($resource, $shortQuery = false, $totalChapters = null, $latestChapter = null)
    {
        parent::__construct($resource);
        $this->shortQuery = $shortQuery;
        $this->totalChapters = $totalChapters;
        $this->latestChapter = $latestChapter;
        
        // Debug logging - remove after testing
        \Log::info('NovelResource Debug', [
            'totalChapters' => $totalChapters,
            'latestChapter' => $latestChapter ? 'exists' : 'null'
        ]);
    }

    public function toArray($request)
    {
        // Debug logging - remove after testing
        \Log::info('NovelResource toArray', [
            'totalChapters' => $this->totalChapters,
            'latestChapter' => $this->latestChapter ? 'exists' : 'null'
        ]);

        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'author_id' => (int) $this->author_id,
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => (int) $this->author->id,
                'name' => $this->author->name,
            ] : null),
            'publisher' => $this->publisher,
            'cover_image_url' => $this->cover_image_url ? asset($this->cover_image_url) : null,
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
            'chapters' => $this->whenLoaded('chapters', fn () => $this->chapters->map(function ($chapter) {
                return new ChapterResource($chapter, $this->shortQuery);
            })),
            'ratings' => $this->whenLoaded('ratings', fn () => $this->ratings->map(function ($rating) {
                return [
                    'id' => (int) $rating->id,
                    'user_id' => (int) $rating->user_id,
                    'rating' => $rating->rating,
                    'created_at' => $rating->created_at->toIso8601String(),
                    'updated_at' => $rating->updated_at->toIso8601String(),
                ];
            })->toArray()),
            'featured' => $this->whenLoaded('featured', fn () => $this->featured ? [
                'id' => (int) $this->featured->id,
                'position' => $this->featured->position,
                'start_date' => $this->featured->start_date->toIso8601String(),
                'end_date' => $this->featured->end_date->toIso8601String(),
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'totalChapters' => $this->totalChapters,
            'latestChapter' => $this->latestChapter ? new ChapterResource($this->latestChapter, $this->shortQuery) : null,
        ];
    }
}