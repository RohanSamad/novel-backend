<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedNovel extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'novel_id',
        'position',
        'start_date',
        'end_date',
    ];

    public function novel()
    {
        return $this->belongsTo(Novel::class, 'novel_id');
    }
}