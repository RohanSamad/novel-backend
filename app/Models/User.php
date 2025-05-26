<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $fillable = [
        'email',
        'password',
        'phone',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class, 'id', 'id');
    }

    public function novelRatings()
    {
        return $this->hasMany(NovelRating::class, 'user_id');
    }
}