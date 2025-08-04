<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Book",
 *     type="object",
 *     title="Book Resource",
 *     required={"id", "category", "name", "author", "total_copies", "available_copies"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     
 *     @OA\Property(
 *         property="category",
 *         oneOf={
 *             @OA\Schema(type="integer", example=2),
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=2),
 *                 @OA\Property(property="name", type="string", example="Fiction")
 *             )
 *         },
 *         description="Either category ID or full category object if loaded"
 *     ),

 *     @OA\Property(property="name", type="string", example="The Great Gatsby"),
 *     @OA\Property(property="author", type="string", example="F. Scott Fitzgerald"),
 *     @OA\Property(property="short_description", type="string", example="A classic novel set in the 1920s", nullable=true),
 *     @OA\Property(property="total_copies", type="integer", example=10),
 *     @OA\Property(property="available_copies", type="integer", example=7),
 *     @OA\Property(property="average_rating", type="integer", example=4),
 *     @OA\Property(property="total_ratings", type="integer", example=100),
 *     @OA\Property(property="rating_count", type="integer", example=25),
 *     @OA\Property(property="book_cover_url", type="string", format="url", example="http://localhost/storage/book_covers/cover.jpg", nullable=true),
 *     @OA\Property(property="pdf_file_url", type="string", format="url", example="http://localhost/storage/pdf_files/book.pdf", nullable=true),
 *     @OA\Property(property="audio_file_url", type="string", format="url", example="http://localhost/storage/audio_files/book.mp3", nullable=true)
 * )
 */
class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'category_name' => $this->category->category_name,
                ];
            }, $this->category_id),

            'name' => $this->name,
            'author' => $this->author,
            'short_description' => $this->short_description,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'average_rating' => $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'rating_count' => $this->rating_count,
            'book_cover_url' => $this->book_cover ? asset('storage/' . $this->book_cover) : null,
            'pdf_file_url' => $this->pdf_file ? asset('storage/' . $this->pdf_file) : null,
            'audio_file_url' => $this->audio_file ? asset('storage/' . $this->audio_file) : null,
        ];
    }

   
}
