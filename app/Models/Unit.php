<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'idsbr',
        'kdkec',
        'kddesa',
        'nama_usaha',
        'alamat',
        'latitude',
        'longitude',
        'status_awal',
        'current_status',
        'status_keberadaan',
        'last_updated_by',
        'first_updated_by',
        'raw_data'
    ];

    protected $casts = [
        'raw_data' => 'array',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
