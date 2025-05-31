<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NovelStatsCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($stats) {
            return new NovelStatsResource($stats);
        })->toArray();
    }
}