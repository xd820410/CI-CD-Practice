<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'cart';
    protected $fillable = [
        'user_id', 'payload'
    ];
    protected $casts = [
        'payload' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
