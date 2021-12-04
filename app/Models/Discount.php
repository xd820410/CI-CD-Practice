<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    const type_single_goods_quantity_threshold = 1;

    protected $fillable = [
        'title', 'type', 'payload'
    ];
    protected $hidden = [
        'deleted_at'
    ];
    protected $casts = [
        'payload' => 'array',
    ];
}
