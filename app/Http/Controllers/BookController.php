<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/book/create",
     *     summary="Create a new book",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     operationId="createBook",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id", "name", "author", "total_copies", "available_copies"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="The Great Gatsby"),
     *                 @OA\Property(property="author", type="string", example="F. Scott Fitzgerald"),
     *                 @OA\Property(property="short_description", type="string", example="A novel set in the 1920s", nullable=true),
     *                 @OA\Property(property="total_copies", type="integer", example=10),
     *                 @OA\Property(property="available_copies", type="integer", example=8),
     *                 @OA\Property(
     *                     property="book_cover",
     *                     type="file",
     *                     description="Book cover image (jpg, jpeg, png, gif)"
     *                 ),
     *                 @OA\Property(
     *                     property="pdf_file",
     *                     type="file",
     *                     description="PDF version of the book"
     *                 ),
     *                 @OA\Property(
     *                     property="audio_file",
     *                     type="file",
     *                     description="Audio version of the book (mp3 or wav)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="book create successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'total_copies' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0',
            'book_cover' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480', // 20MB
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 400);
        }
        if ($request->hasFile('book_cover')) {
            $coverPath = $request->file('book_cover')->store('covers', 'public');
            $request->merge(['book_cover' => $coverPath]);
        }
        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('pdfs', 'public');
            $request->merge(['pdf_file' => $pdfPath]);
        }
        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('audios', 'public');
            $request->merge(['audio_file' => $audioPath]);
        }
        // Assuming you have a Booking model to handle the database interaction
        $book = Book::create($request->all());
        return response()->json([
            'message' => 'book create successfully',
            'data' => new BookResource($book),
        ], 201);
    }
}
