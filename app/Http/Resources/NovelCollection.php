<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NovelCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($novel) {
            return new NovelResource($novel);
        })->toArray();
    }
}