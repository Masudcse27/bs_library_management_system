<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Borrow",
 *     type="object",
 *     required={"id", "borrowed_at", "return_date", "status", "extension_count"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="borrowed_at", type="string", format="date-time", example="2025-07-01T10:00:00Z"),
 *     @OA\Property(property="return_date", type="string", format="date-time", example="2025-07-31T23:59:59Z"),
 *     @OA\Property(property="status", type="string", example="borrowed"),
 *     @OA\Property(property="returned_at", type="string", format="date-time", nullable=true, example="2025-07-25T14:30:00Z"),
 *     @OA\Property(property="extension_count", type="integer", example=1),
 *     
 *     @OA\Property(
 *         property="book",
 *         ref="#/components/schemas/Book",
 *         nullable=true
 *     ),
 *     
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         nullable=true
 *     )
 * )
 */
class BorrowResource extends JsonResource
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
            'borrowed_at' => $this->borrowed_at,
            'return_date' => $this->return_date,
            'status' => $this->status,
            'returned_at' => $this->returned_at,
            'extension_count' => $this->extension_count,
            'book' => $this->whenLoaded('book', function () {
                return new BookResource($this->book);
            }, $this->book_id),

            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }, $this->user_id),
        ];
    }
}
