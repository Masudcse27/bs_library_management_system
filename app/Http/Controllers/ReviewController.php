<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/review/{book_id}/create",
     *     summary="Create a new review for a book",
     *     description="Allows an authenticated user to submit a review for a specific book",
     *     operationId="createReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="book_id",
     *         in="path",
     *         description="ID of the book to review",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="comment", type="string", maxLength=1000, example="Really insightful and well-written."),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review created successfully"),
     *             @OA\Property(property="review", ref="#/components/schemas/Review")
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
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function create(Request $request, $book_id)
    {
        $validatedData = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validatedData->fails()) {
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        // Ensure book exists
        $book = Book::find($book_id);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $data = $validatedData->validated();
        $data['book_id'] = $book_id;
        $data['user_id'] = $request->user()->id;

        try {
            $review = DB::transaction(function () use ($book, $data) {
                $total_ratings = $book->total_ratings + $data['rating'];
                $rating_count = $book->rating_count + 1;
                $average_rating = $total_ratings / $rating_count;

                $book->update([
                    'total_ratings' => $total_ratings,
                    'rating_count' => $rating_count,
                    'average_rating' => $average_rating,
                ]);

                return Review::create($data);
            });

            return response()->json([
                'message' => 'Review created successfully',
                'review' => new ReviewResource($review),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Review creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'book_id' => $book_id,
            ]);

            return response()->json([
                'message' => 'Failed to create review. Please try again later.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/review/{book_id}/list",
     *     summary="Get list of reviews for a book",
     *     description="Returns a list of reviews for the specified book",
     *     operationId="getBookReviews",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="book_id",
     *         in="path",
     *         description="ID of the book",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reviews",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Review")
     *             )
     *         )
     *     )
     * )
     */
    public function list($book_id)
    {
        $reviews = Review::where('book_id', $book_id)->get();
        return response()->json(['reviews' => ReviewResource::collection($reviews)], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/review/retrieve/{id}",
     *     summary="Get a single review",
     *     description="Returns the review for the given ID",
     *     operationId="getReviewById",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the review",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Review retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="review",
     *                 ref="#/components/schemas/Review"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review not found")
     *         )
     *     )
     * )
     */
    public function retrieve($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        return response()->json(['review' => new ReviewResource($review)], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/review/edit/{id}",
     *     summary="Update a review",
     *     description="Allows the review owner to update their review",
     *     operationId="updateReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the review",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="comment", type="string", maxLength=1000, example="Updated comment about the book.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=202,
     *         description="Review updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review updated successfully"),
     *             @OA\Property(property="review", ref="#/components/schemas/Review")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are not authorized to update this review")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Review not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validatedData = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validatedData->fails()) {
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not authorized to update this review'], 403);
        }

        $data = $validatedData->validated();

        try {
            DB::transaction(function () use (&$review, $data) {
                $book = Book::find($review->book_id);

                $total_ratings = $book->total_ratings - $review->rating + $data['rating'];
                $rating_count = $book->rating_count;
                $average_rating = $rating_count > 0 ? $total_ratings / $rating_count : 0;

                $book->update([
                    'total_ratings' => $total_ratings,
                    'average_rating' => $average_rating,
                ]);

                $review->update($data);
            });

            return response()->json([
                'message' => 'Review updated successfully',
                'review' => new ReviewResource($review),
            ], 202);

        } catch (\Exception $e) {
            Log::error('Review update failed', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to update review. Please try again later.',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/review/delete/{id}",
     *     summary="Delete a review",
     *     description="Deletes a review if the authenticated user is the owner",
     *     operationId="deleteReview",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the review to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are not authorized to delete this review")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Review not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Review not found")
     *         )
     *     )
     * )
     */
    public function delete(Request $request, $id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not authorized to delete this review'], 403);
        }

        try {
            DB::transaction(function () use ($review) {
                $book = Book::find($review->book_id);

                $total_ratings = $book->total_ratings - $review->rating;
                $rating_count = $book->rating_count - 1;
                $average_rating = $rating_count > 0 ? $total_ratings / $rating_count : 0;

                $book->update([
                    'total_ratings' => $total_ratings,
                    'rating_count' => $rating_count,
                    'average_rating' => $average_rating,
                ]);

                $review->delete();
            });

            return response()->json(['message' => 'Review deleted successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Review deletion failed', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to delete review. Please try again later.',
            ], 500);
        }
    }
}
