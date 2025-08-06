<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'name',
        'author',
        'short_description',
        'total_copies',
        'available_copies',
        'book_cover',
        'pdf_file',
        'audio_file',
        'category_id',
        'total_ratings',
        'rating_count',
        'average_rating',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
