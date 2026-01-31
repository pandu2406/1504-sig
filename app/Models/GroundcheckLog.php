<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line

class GroundcheckLog extends Model
{
    protected $fillable = [
        'unit_id',
        'user_id',
        'action',
        'old_values',
        'new_values'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
