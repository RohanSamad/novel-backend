<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NovelRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'novel_id',
        'user_id',
        'rating',
    ];

    public function novel()
    {
        return $this->belongsTo(Novel::class, 'novel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}