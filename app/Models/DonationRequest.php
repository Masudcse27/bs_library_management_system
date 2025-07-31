<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'book_title',
        'author_name',
        'status',
        'number_of_copies',
        'location',
        'contact_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
