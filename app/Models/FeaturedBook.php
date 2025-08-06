<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedBook extends Model
{
    protected $fillable = ['book_id'];

    /**
     * Get the book associated with the featured book.
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
