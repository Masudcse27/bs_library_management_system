<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Settings",
 *     type="object",
 *     title="Settings",
 *     required={"max_borrow_duration", "max_borrow_limit", "max_extension_limit", "max_booking_duration", "max_booking_limit"},
 *     @OA\Property(property="max_borrow_duration", type="integer", example=30),
 *     @OA\Property(property="max_borrow_limit", type="integer", example=3),
 *     @OA\Property(property="max_extension_limit", type="integer", example=2),
 *     @OA\Property(property="max_booking_duration", type="integer", example=7),
 *     @OA\Property(property="max_booking_limit", type="integer", example=3)
 * )
 */
class SettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'max_borrow_duration' => $this->max_borrow_duration,
            'max_borrow_limit' => $this->max_borrow_limit,
            'max_extension_limit' => $this->max_extension_limit,
            'max_booking_duration' => $this->max_booking_duration,
            'max_booking_limit' => $this->max_booking_limit
        ];
    }
}
