<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NovelStats extends Model
{
    use HasFactory;

    protected $primaryKey = 'id'; // Matches the 'id' column that references novels.id
    protected $fillable = [
        'title',
        'chapter_count',
        'reader_count',
        'average_rating',
        'rating_count',
        'total_views',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
        'average_rating' => 'float',
    ];

    public function novel()
    {
        return $this->belongsTo(Novel::class, 'id');
    }
}