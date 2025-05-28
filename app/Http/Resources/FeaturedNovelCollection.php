<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FeaturedNovelCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($featuredNovel) {
            return new FeaturedNovelResource($featuredNovel);
        })->toArray();
    }
}