<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="DonationRequest",
 *     type="object",
 *     title="DonationRequest",
 *     required={"id", "user_id", "book_title", "status", "number_of_copies", "contact_number"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="book_title", type="string", example="Introduction to Algorithms"),
 *     @OA\Property(property="author_name", type="string", example="Thomas H. Cormen"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="number_of_copies", type="integer", example=2),
 *     @OA\Property(property="location", type="string", example="Dhaka Library Zone 3"),
 *     @OA\Property(property="contact_number", type="string", example="+8801700000000")
 * )
 */
class DonationRequestResource extends JsonResource
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
            'user_id' => $this->user_id,
            'book_title' => $this->book_title,
            'author_name' => $this->author_name,
            'status' => $this->status,
            'number_of_copies' => $this->number_of_copies,
            'location' => $this->location,
            'contact_number' => $this->contact_number
        ];
    }
}
