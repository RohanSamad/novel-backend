<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    protected $shortQuery;

    public function __construct($resource, $shortQuery = false)
    {
        parent::__construct($resource);
        $this->shortQuery = $shortQuery;
    }

    public function toArray($request)
    {
        $data = [
            'id'             => (int) $this->id,
            'novel_id'       => (int) $this->novel_id,
            'chapter_number' => $this->chapter_number,
            'title'          => $this->title,
            'order_index'    => $this->order_index,
            'created_at'     => $this->created_at->toIso8601String(),
            'updated_at'     => $this->updated_at->toIso8601String(),
        ];

        if (!$this->shortQuery) {
            $data['audio_url']   = $this->audio_url ? asset($this->audio_url) : null;
            $data['content_text'] = $this->content_text;
        }

        return $data;
    }
}
