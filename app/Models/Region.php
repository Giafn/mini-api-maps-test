<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'tileset_id',
        'level',
        'code',
        'name',
    ];

    public function tileset()
    {
        return $this->belongsTo(Tileset::class);
    }
}
