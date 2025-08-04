<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     required={"id", "book_id", "user_id", "rating"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="book_id", type="integer", example=10),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
 *     @OA\Property(property="comment", type="string", example="Great book on Laravel!")
 * )
 */
class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'book_id'    => $this->book_id,
            'user_id'    => $this->user_id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
        ];
    }
}
