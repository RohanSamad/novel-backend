<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'novel_id',
        'chapter_number',
        'title',
        'audio_url',
        'content_text',
        'order_index',
    ];

    public function novel()
    {
        return $this->belongsTo(Novel::class, 'novel_id');
    }
}