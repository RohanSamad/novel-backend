<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
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
}