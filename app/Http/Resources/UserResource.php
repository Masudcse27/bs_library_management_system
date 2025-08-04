<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "name", "email", "role"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="role", type="string", example="customer"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
 *     @OA\Property(property="address", type="string", example="123 Main St, Dhaka, Bangladesh"),
 *     @OA\Property(
 *         property="profile_picture",
 *         type="string",
 *         format="uri",
 *         nullable=true,
 *         example="https://example.com/storage/users/john.jpg"
 *     )
 * )
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'date_of_birth' => $this->date_of_birth,
            'address' => $this->address,
            'profile_picture' => $this->profile_picture,
        ];
    }
}
