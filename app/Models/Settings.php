<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'max_borrow_duration',
        'max_borrow_limit',
        'max_extension_count',
        'max_booking_duration',
        'max_booking_limit',
    ];

}
