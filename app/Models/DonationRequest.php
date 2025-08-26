<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'book_title',
        'status',
        'number_of_copies',
        'bs_id',
        'sbu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
