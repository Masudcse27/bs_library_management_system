<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
