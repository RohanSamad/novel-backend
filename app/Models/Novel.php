<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author_id',
        'publisher',
        'cover_image_url',
        'synopsis',
        'status',
        'publishing_year',
    ];

    protected $attributes = [
        'status' => 'ongoing',
    ];

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class, 'novel_id');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'novel_genres', 'novel_id', 'genre_id');
    }

    public function ratings()
    {
        return $this->hasMany(NovelRating::class, 'novel_id');
    }

   public function stats()
{
    return $this->hasOne(NovelStats::class, 'novel_id', 'id');
}


    public function featured()
    {
        return $this->hasOne(FeaturedNovel::class, 'novel_id');
    }

    public static $validStatuses = ['completed', 'ongoing', 'hiatus'];

    public function setStatusAttribute($value)
    {
        if (!in_array($value, self::$validStatuses)) {
            throw new \InvalidArgumentException("Invalid status value: {$value}");
        }
        $this->attributes['status'] = $value;
    }

    /**
     * Find a novel by its slug (slugified title) or exact title.
     * Handles both hyphen-separated slugs and space-separated titles.
     *
     * @param  string  $slug  The slug or title to search for
     * @param  array   $with  Eager load relationships
     * @return \App\Models\Novel|null
     */
    public static function findBySlug($slug, array $with = [])
    {
        // Normalize the input: remove all non-alphanumeric characters and lowercase
        $normalizedSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $slug));

        // First try exact title match (for backwards compatibility)
        $query = empty($with) ? self::query() : self::with($with);
        $novel = $query->where('title', $slug)->first();

        if ($novel) {
            return $novel;
        }

        // Try URL decoded exact match
        $decodedSlug = urldecode($slug);
        $query = empty($with) ? self::query() : self::with($with);
        $novel = $query->where('title', $decodedSlug)->first();

        if ($novel) {
            return $novel;
        }

        // Try slug-based matching: compare normalized versions
        // This matches where the title, when stripped of non-alphanumeric chars, equals the slug
        $query = empty($with) ? self::query() : self::with($with);
        $novel = $query->whereRaw(
            "LOWER(REGEXP_REPLACE(title, '[^a-zA-Z0-9]', '', 'g')) = ?",
            [$normalizedSlug]
        )->first();

        return $novel;
    }
}