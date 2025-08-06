<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="FeaturedBook",
 *     type="object",
 *     title="FeaturedBook",
 *     required={"id", "book"},
 *     
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     
 *     @OA\Property(
 *         property="book",
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/Book"),
 *             @OA\Schema(type="integer", example=10)
 *         },
 *         description="Book object if loaded, otherwise book ID"
 *     )
 * )
 */
class FeaturedBookResource extends JsonResource
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
            'book' => $this->whenLoaded('book', function () {
                return new BookResource($this->book);
            }, $this->book_id)
        ];
    }
}
