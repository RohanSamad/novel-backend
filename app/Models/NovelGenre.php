<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NovelGenre extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = ['novel_id', 'genre_id'];

    public $incrementing = false;

    protected $fillable = [
        'novel_id',
        'genre_id',
    ];

    public function novel()
    {
        return $this->belongsTo(Novel::class, 'novel_id');
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }
}