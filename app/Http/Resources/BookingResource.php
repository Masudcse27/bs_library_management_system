<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     title="Booking Resource",
 *     required={"id", "user_id", "book_id", "status", "booking_date", "expiry_date"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="book_id", type="integer", example=12),
 *     @OA\Property(property="status", type="string", example="pending", description="Status of the booking (e.g., pending, approved, cancelled)"),
 *     @OA\Property(property="booking_date", type="string", format="date", example="2025-08-04"),
 *     @OA\Property(property="expiry_date", type="string", format="date", example="2025-08-11"),
 *
 *     @OA\Property(
 *         property="book",
 *         oneOf={
 *             @OA\Schema(type="integer", example=12),
 *             @OA\Schema(ref="#/components/schemas/Book")
 *         },
 *         description="Either book ID or full book object if loaded"
 *     ),
 *
 *     @OA\Property(
 *         property="user",
 *         oneOf={
 *             @OA\Schema(type="integer", example=5),
 *             @OA\Schema(ref="#/components/schemas/User")
 *         },
 *         description="Either user ID or full user object if loaded"
 *     ),
 *
 *     @OA\Property(
 *         property="Borrow",
 *         oneOf={
 *             @OA\Schema(type="integer", example=7),
 *             @OA\Schema(ref="#/components/schemas/Borrow")
 *         },
 *         description="Either borrow ID or full borrow object if loaded"
 *     )
 * )
 */
class BookingResource extends JsonResource
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
            'book_id' => $this->book_id,
            'status' => $this->status,
            'booking_date' => $this->booking_date,
            'expiry_date' => $this->expiry_date,
            'book' => $this->whenLoaded('book', function () {
                return new BookResource($this->book);
            }, $this->book_id),

            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }, $this->user_id),
            'Borrow' => $this->whenLoaded('borrow', function () {
                return new BorrowResource($this->borrow);
            }, $this->borrow_id),
        ];
    }
}
