<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SipwData extends Model
{
    protected $table = 'sipw_data';

    protected $fillable = [
        'sls_name',
        'business_count'
    ];
}
