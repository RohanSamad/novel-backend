<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ChapterCollection extends ResourceCollection
{
    protected $shortQuery;

    public function __construct($resource, $shortQuery = false)
    {
        parent::__construct($resource);
        $this->shortQuery = $shortQuery;
    }

    public function toArray($request)
    {
        return $this->collection->map(function ($chapter) use ($request) {
            return (new ChapterResource($chapter, $this->shortQuery))->toArray($request);
        })->all();
    }
}
