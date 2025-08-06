<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Borrow;
use App\Models\Settings;
use Illuminate\Http\Request;

class BookingController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/bookings/{borrow_id}",
     *     summary="Create a new booking",
     *     description="Creates a booking for a borrow record. The booking_date and expiry_date are automatically determined on the server.",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     
     *
     *     @OA\Parameter(
     *         name="borrow_id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record to create a booking for",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking created successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Booking already exists, over limit, or invalid return date",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking already exists for this borrow")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Borrow record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Borrow record not found")
     *         )
     *     )
     * )
     */
    public function create(Request $request, $borrow_id)
    {
        $borrow = Borrow::find($borrow_id);
        if (!$borrow) {
            return response()->json(['error' => 'Borrow record not found'], 404);
        }

        $user = $request->user();
        $limitation = Settings::first();

        $booking_exists = Booking::where('borrow_id', $borrow_id)
            ->where('status', 'pending')
            ->exists();

        if ($booking_exists) {
            return response()->json(['error' => 'Booking already exists for this borrow'], 403);
        }

        $booking_count = Booking::where('user_id', $user->id)
            ->where('book_id', $borrow->book_id)
            ->where('status', 'pending')
            ->count();

        if ($booking_count >= $limitation->max_booking_limit) {
            return response()->json(['error' => 'Booking limit reached'], 403);
        }

        $maxWaitDate = now()->addDays($limitation->max_booking_duration);
        if ($borrow->return_date > $maxWaitDate) {
            return response()->json([
                'error' => 'This book will not be available within the allowed booking duration.'
            ], 403);
        }

        $booking = Booking::create([
            'user_id' => $user->id,
            'book_id' => $borrow->book_id,
            'borrow_id' => $borrow->id,
            'booking_date' => $borrow->return_date,
            'expiry_date' => $borrow->return_date->copy()->addDays(7),
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => new BookingResource($booking)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="List all bookings",
     *     description="Retrieve a list of bookings. Admins get all bookings with 'pending' or 'available' status. Normal users get their own bookings only.",
     *     operationId="getBookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Bookings retrieved successfully"),
     *             @OA\Property(
     *                 property="bookings",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Booking")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function list(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $bookings = Booking::with(['book', 'user', 'borrow'])->where('status', 'pending')->orWhere('status', 'available')->get();
        } else {
            $bookings = Booking::with(['book', 'user', 'borrow'])
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere('status', 'available');
                })
                ->get();
        }

        return response()->json([
            'message' => 'Bookings retrieved successfully',
            'bookings' => BookingResource::collection($bookings)
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     summary="Retrieve a specific booking",
     *     description="Get a booking by ID. Only the owner or an admin can access the booking details.",
     *     operationId="getBookingById",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Booking retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Booking retrieved successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking not found")
     *         )
     *     )
     * )
     */
    public function retrieve(Request $request, $id)
    {
        $booking = Booking::with(['book', 'user', 'borrow'])->find($id);

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $user = $request->user();

        if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        return response()->json([
            'message' => 'Booking retrieved successfully',
            'booking' => new BookingResource($booking),
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/bookings/{id}/collect",
     *     summary="Collect a booking",
     *     description="Allows a user to mark their own booking as collected if it's available.",
     *     operationId="collectBooking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=202,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Booking updated successfully"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access or booking not available",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking is not available for collection")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking not found")
     *         )
     *     )
     * )
     */
    public function collect(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $user = $request->user();
        if ($booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        if ($booking->status !== 'available') {
            return response()->json(['error' => 'Booking is not available for collection'], 403);
        }

        $booking->update(['status' => 'collected']);

        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => new BookingResource($booking)
        ], 202);
    }
    /**
     * @OA\Delete(
     *     path="/api/bookings/{id}",
     *     summary="Delete a booking",
     *     description="Allows an admin or the booking owner to delete a booking.",
     *     operationId="deleteBooking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the booking to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Booking deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking not found")
     *         )
     *     )
     * )
     */
    public function delete(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully'], 200);
    }
}
