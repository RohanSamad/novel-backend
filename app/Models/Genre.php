<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function novels()
    {
        return $this->belongsToMany(Novel::class, 'novel_genres', 'genre_id', 'novel_id');
    }
}