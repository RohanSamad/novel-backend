<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NovelRatingCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($rating) {
            return new NovelRatingResource($rating);
        })->toArray();
    }
}