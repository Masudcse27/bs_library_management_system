<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowResource;
use App\Models\Book;
use App\Models\Booking;
use App\Models\Borrow;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BorrowController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/borrow/create",
     *     summary="Create a new borrow record (borrow a book)",
     *     tags={"Borrow"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"book_id"},
     *             @OA\Property(property="book_id", type="integer", example=10, description="ID of the book to borrow"),
     *             @OA\Property(property="return_date", type="string", format="date", nullable=true, example="2025-08-30", description="Optional return date, must be today or later")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book borrowed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book borrowed successfully"),
     *             @OA\Property(property="borrow", ref="#/components/schemas/Borrow")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or no available copies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No available copies")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred"),
     *             @OA\Property(property="details", type="string", example="SQLSTATE[23000]: Integrity constraint violation...")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validated_data = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'return_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validated_data->fails()) {
            return response()->json(['message' => $validated_data->errors()], 422);
        }

        // Use DB transaction to ensure atomic operation
        try {
            DB::beginTransaction();

            $activeBorrowCount = Borrow::where('user_id', $request->user()->id)
                ->where('status', 'borrowed')
                ->count();

            $borrowLimit = Settings::value('max_borrow_limit') ?? 5;
            $maxBorrowDuration = Settings::value('max_borrow_duration') ?? 14;

            if ($activeBorrowCount >= $borrowLimit) {
                DB::rollBack();
                return response()->json([
                    'message' => "You have reached the borrow limit of {$borrowLimit} books."
                ], 422);
            }
            $book = Book::find($request->book_id);
            if (!$book) {
                DB::rollBack();
                return response()->json(['message' => 'Book not found'], 404);
            }

            if ($book->available_copies <= 0) {
                DB::rollBack();
                return response()->json(['message' => 'No available copies'], 422);
            }

            if ($request->return_date) {
                $returnDate = \Carbon\Carbon::parse($request->return_date);
                $maxAllowedDate = now()->addDays($maxBorrowDuration)->startOfDay();

                if ($returnDate->greaterThan($maxAllowedDate)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "The return date cannot exceed {$maxBorrowDuration} days from today."
                    ], 422);
                }
            }

            $borrow = new Borrow();
            $borrow->book_id = $request->book_id;
            $borrow->user_id = auth()->id();
            $borrow->borrowed_at = now();
            $borrow->return_date = $request->return_date;
            $borrow->save();

            $book->available_copies -= 1;
            $book->save();

            DB::commit();

            return response()->json(['message' => 'Book borrowed successfully', 'borrow' => new BorrowResource($borrow)], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An unexpected error occurred', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/borrow/list",
     *     summary="List borrowed books",
     *     tags={"Borrow"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="book_id",
     *         in="query",
     *         description="Filter by book ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of borrowed books",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="borrows",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Borrow")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - missing or invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token not provided or invalid")
     *         )
     *     )
     * )
     */
    public function list(Request $request)
    {
        $user = $request->user();

        $query = Borrow::with('book', 'user')->where('status', 'borrowed');

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('book_id')) {
            $query->where('book_id', $request->query('book_id'));
        }

        $borrows = $query->get();

        return response()->json([
            'borrows' => BorrowResource::collection($borrows)
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/borrow/retrieve/{id}",
     *     summary="Retrieve a borrow record",
     *     description="Retrieve a specific borrow record by ID. Admins can access all records, while normal users can only access their own.",
     *     operationId="getBorrowRecord",
     *     tags={"Borrow"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful borrow record retrieval",
     *         @OA\JsonContent(
     *             @OA\Property(property="borrow", ref="#/components/schemas/Borrow")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access to the borrow record"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Borrow record not found"
     *     )
     * )
     */
    public function retrieve(Request $request, $id)
    {
        $borrow = Borrow::with('book', 'user')->find($id);

        if (!$borrow) {
            return response()->json(['message' => 'Borrow record not found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $borrow->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'borrow' => new BorrowResource($borrow),
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/borrow/extend/{id}",
     *     summary="Extend return date of a borrow record",
     *     description="Allows the authenticated user to extend the return date of a borrow, if no in-progress booking exists and extension limit is not exceeded.",
     *     operationId="extendBorrow",
     *     tags={"Borrow"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record to extend",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"return_date"},
     *             @OA\Property(property="return_date", type="string", format="date", example="2025-08-15")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Borrow record extended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Borrow record extended successfully"),
     *             @OA\Property(property="borrow", ref="#/components/schemas/Borrow")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Extension blocked due to active booking or extension limit reached",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Extension limit exceeded")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized — user doesn't own the borrow record",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Borrow record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Borrow record not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid return_date)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object")
     *         )
     *     )
     * )
     */
    public function extend(Request $request, $id)
    {
        // 1. Validate input
        $validator = Validator::make($request->all(), [
            'return_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        // 2. Find borrow record
        $borrow = Borrow::find($id);
        if (!$borrow) {
            return response()->json(['message' => 'Borrow record not found'], 404);
        }

        // 3. Check ownership
        if ($request->user()->id !== $borrow->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 4. Check for any booking that prevents extension
        $bookingExists = Booking::where('borrow_id', $borrow->id)
            ->where('status', 'in_progress')
            ->exists();

        if ($bookingExists) {
            return response()->json(['message' => 'Book already booked — cannot extend'], 400);
        }

        // 5. Check extension limit
        $limit = Settings::value('max_extension_limit');
        if (!is_null($limit) && $borrow->extension_count >= $limit) {
            return response()->json(['message' => 'Extension limit exceeded'], 400);
        }

        // 6. Enforce maximum borrow duration
        $maxBorrowDuration = Settings::value('max_borrow_duration') ?? 14;

        $returnDate = Carbon::parse($request->return_date);
        $borrowedAt = Carbon::parse($borrow->borrowed_at); // Ensure Carbon instance
        $maxAllowedDate = $borrowedAt->copy()->addDays($maxBorrowDuration)->startOfDay();

        if ($returnDate->greaterThan($maxAllowedDate)) {
            return response()->json([
                'message' => "The return date cannot exceed {$maxBorrowDuration} days from the borrow date ({$borrowedAt->format('Y-m-d')})."
            ], 422);
        }

        // 7. Update borrow record
        $borrow->update([
            'return_date' => $returnDate->format('Y-m-d'),
            'extension_count' => $borrow->extension_count + 1,
        ]);

        // 8. Return success response
        return response()->json([
            'message' => 'Borrow record extended successfully',
            'borrow' => new BorrowResource($borrow),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/borrow/return/{id}",
     *     summary="Return a borrowed book",
     *     description="Allows a user to return a borrowed book. The borrow status is marked as 'returned'. If no upcoming booking exists, the available copies of the book are incremented.",
     *     operationId="returnBorrow",
     *     tags={"Borrow"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record",
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Book returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book returned successfully"),
     *             @OA\Property(property="borrow", ref="#/components/schemas/Borrow")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized – user doesn't own the borrow record",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Borrow record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Borrow record not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function return(Request $request, $id)
    {
        $borrow = Borrow::find($id);
        if (!$borrow) {
            return response()->json(['message' => 'Borrow record not found'], 404);
        }

        // Check ownership
        if ($request->user()->id !== $borrow->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $book = Book::find($borrow->book_id);
            $bookingExists = Booking::where('borrow_id', $borrow->id)
                ->where('status', 'pending')
                ->first();

            // Mark the book as returned
            $borrow->update([
                'status' => 'returned',
                'returned_at' => now()
            ]);

            // Update booking or increase available copies
            if ($bookingExists) {
                $bookingExists->update(['status' => 'available']);
            } else {
                $book->increment('available_copies');
            }

            DB::commit();

            return response()->json([
                'message' => 'Book returned successfully',
                'booking' => $bookingExists,
                'borrow' => new BorrowResource($borrow),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in book return: ' . $e->getMessage());

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
