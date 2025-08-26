<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="DonationRequest",
 *     type="object",
 *     title="DonationRequest",
 *     required={"id", "user_id", "book_title", "status", "number_of_copies", "bs_id", "sbu"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="book_title", type="string", example="Introduction to Algorithms"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="number_of_copies", type="integer", example=2),
 *     @OA\Property(property="bs_id", type="string", example="BS123"),
 *     @OA\Property(property="sbu", type="string", example="SBU456")
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
            'book_title' => $this->book_title,
            'status' => $this->status,
            'number_of_copies' => $this->number_of_copies,
            'bs_id' => $this->bs_id,
            'sbu' => $this->sbu,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }, $this->user_id),

        ];
    }
}
