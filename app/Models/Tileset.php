<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tileset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'original_file_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function regions()
    {
        return $this->hasMany(Region::class);
    }
}
