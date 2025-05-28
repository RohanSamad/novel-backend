<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AuthorCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($author) {
            return new AuthorResource($author);
        })->toArray();
    }
}