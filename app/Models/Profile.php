<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_sign_in_at' => 'datetime',
    ];

    protected $fillable = [
        'username',
        'role',
        'last_sign_in_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }
}