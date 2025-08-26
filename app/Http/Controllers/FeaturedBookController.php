<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeaturedBookResource;
use App\Models\Book;
use App\Models\FeaturedBook;
use Illuminate\Http\Request;

class FeaturedBookController extends Controller
{
    /**
     * Add a book to the featured list.
     */

    /**
     * @OA\Post(
     *     path="/api/featured-books/{book_id}/add",
     *     summary="Add a book to the featured list",
     *     description="Allows admin users to add a book to the featured list by its ID.",
     *     operationId="addFeaturedBook",
     *     tags={"Featured Books"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="book_id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to feature",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Book added to featured list successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book added to featured list successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book not found")
     *         )
     *     )
     * )
     */
    public function addFeaturedBook(Request $request, $book_id)
    {
        $user = request()->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $book = Book::find($book_id);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $featuredBook = new FeaturedBook();
        $featuredBook->book_id = $book_id;
        $featuredBook->save();
        return response()->json(['message' => 'Book added to featured list successfully'], 201);
    }

    /**
     * List all featured books.
     */

    /**
     * @OA\Get(
     *     path="/api/featured-books/list",
     *     summary="List all featured books",
     *     description="Returns a list of all books marked as featured.",
     *     operationId="listFeaturedBooks",
     *     tags={"Featured Books"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of featured books",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/FeaturedBook")
     *         )
     *     )
     * )
     */
    public function list()
    {
        $featuredBooks = FeaturedBook::with('book','book.category')->paginate(9);
        return FeaturedBookResource::collection($featuredBooks)
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove a book from the featured list.
     */

    /**
     * @OA\Delete(
     *     path="/api/featured-books/remove/{id}",
     *     summary="Remove a book from the featured list",
     *     description="Allows admin users to remove a book from the featured list.",
     *     operationId="removeFeaturedBook",
     *     tags={"Featured Books"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the featured book",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Book removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book removed from featured list successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Featured book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Featured book not found")
     *         )
     *     )
     * )
     */
    public function removeFeaturedBook(Request $request, $id)
    {
        $user = request()->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $featuredBook = FeaturedBook::find($id);
        if (!$featuredBook) {
            return response()->json(['message' => 'Featured book not found'], 404);
        }

        $featuredBook->delete();
        return response()->json(['message' => 'Book removed from featured list successfully'], 200);
    }
}
