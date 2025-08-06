<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'borrow_id',
        'booking_date',
        'expiry_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
    public function borrow()
    {
        return $this->belongsTo(Borrow::class);
    }
}
