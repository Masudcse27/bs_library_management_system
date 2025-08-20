<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     *                 required={"category_id", "name", "author", "total_copies"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="The Great Gatsby"),
     *                 @OA\Property(property="author", type="string", example="F. Scott Fitzgerald"),
     *                 @OA\Property(property="short_description", type="string", example="A novel set in the 1920s", nullable=true),
     *                 @OA\Property(property="total_copies", type="integer", example=10),
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
            // 'available_copies' => 'required|integer|min:0',
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
            $coverPath = $request->file('book_cover')->store('book/covers', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('book/pdfs', 'public');
        }

        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('book/audios', 'public');
        }
        // Assuming you have a Booking model to handle the database interaction
        // $book = Book::create($request->all());
        $book = Book::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'author' => $request->author,
            'short_description' => $request->short_description,
            'total_copies' => $request->total_copies,
            'available_copies' => $request->total_copies,
            'book_cover' => $coverPath ?? null,
            'pdf_file' => $pdfPath ?? null,
            'audio_file' => $audioPath ?? null,
        ]);
        return response()->json([
            'message' => 'book create successfully',
            'data' => new BookResource($book),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/book/list",
     *     summary="Get list of books with optional filters and pagination",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by book name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (pagination)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function list(Request $request)
    {

        $query = Book::with('category');
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->query('name') . '%');
        }

        if ($request->has('author')) {
            $query->where('author', 'like', '%' . $request->query('author') . '%');
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->query('category'));
        }

        $books = $query->paginate($request->query('per_page', 10));

        return BookResource::collection($books)
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/book/retrieve/{id}",
     *     summary="Retrieve a single book by ID",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the book to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of book details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", ref="#/components/schemas/Book")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book with this id is not found")
     *         )
     *     )
     * )
     */
    public function retrieve(Request $request, $id)
    {
        $book = Book::with('category')->find($id);

        if (!$book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book with this ID was not found.',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => new BookResource($book),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/book/edit/{id}",
     *     summary="Update an existing book",
     *     description="Update book information including files (cover, PDF, audio). Use POST with _method=PUT for file uploads.",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"_method", "category_id", "name", "author", "total_copies", "available_copies"},
     *                 @OA\Property(
     *                     property="_method",
     *                     type="string",
     *                     example="PUT",
     *                     description="HTTP method override to support PUT with multipart/form-data"
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Advanced Laravel"
     *                 ),
     *                 @OA\Property(
     *                     property="author",
     *                     type="string",
     *                     example="Masud Bhuiyan"
     *                 ),
     *                 @OA\Property(
     *                     property="short_description",
     *                     type="string",
     *                     example="A deep dive into Laravel."
     *                 ),
     *                 @OA\Property(
     *                     property="total_copies",
     *                     type="integer",
     *                     example=10
     *                 ),
     *                 @OA\Property(
     *                     property="available_copies",
     *                     type="integer",
     *                     example=5
     *                 ),
     *                 @OA\Property(
     *                     property="book_cover",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional book cover image"
     *                 ),
     *                 @OA\Property(
     *                     property="pdf_file",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional PDF version of the book"
     *                 ),
     *                 @OA\Property(
     *                     property="audio_file",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional audio version of the book"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(["message" => "Book not found"], 400);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'total_copies' => 'required|integer|min:1',
            'book_cover' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480',
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 400);
        }
        $availableCopies = $request->available_copies + (abs($book->total_copies - $book->available_copies));

        $book->category_id = $request->category_id;
        $book->name = $request->name;
        $book->author = $request->author;
        $book->short_description = $request->short_description;
        $book->total_copies = $request->total_copies;
        $book->available_copies = $availableCopies;

        if ($request->hasFile('book_cover')) {
            $book->book_cover = $request->file('book_cover')->store('book_covers', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            $book->pdf_file = $request->file('pdf_file')->store('pdfs', 'public');
        }

        if ($request->hasFile('audio_file')) {
            $book->audio_file = $request->file('audio_file')->store('audios', 'public');
        }

        $book->save();

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => $request->all(),
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/book/delete/{id}",
     *     summary="Delete a book by ID",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Book not found"
     *     )
     * )
     */
    public function delete(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(["message" => "Book not found"], 400);
        }

        $book->delete();

        return response()->json(["message" => "Book deleted successfully"], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/book/popular-books",
     *     summary="Get top 20 popular books",
     *     description="Returns the 20 most borrowed books with their category details",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of popular books",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Top 20 popular books"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     )
     * )
     */
    public function popular_books(Request $request)
    {
        $borrowCounts = DB::table('borrows')
            ->select('book_id', DB::raw('COUNT(*) as borrow_count'))
            ->groupBy('book_id');

        // Join subquery with books, order by borrow_count
        $books = Book::select('books.*')
            ->leftJoinSub($borrowCounts, 'borrow_counts', function ($join) {
                $join->on('books.id', '=', 'borrow_counts.book_id');
            })
            ->orderByDesc('borrow_counts.borrow_count')
            ->with('category')
            ->limit(20)
            ->get();


        return response()->json([
            'message' => 'Top 20 popular books',
            'data' => BookResource::collection($books),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/book/new-collection",
     *     summary="Get latest 20 books",
     *     description="Returns the 20 most recently added books with category details",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of latest books",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Latest 20 books"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     )
     * )
     */
    public function new_collection(Request $request)
    {
        $books = Book::with('category')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'message' => 'Latest 20 books',
            'data' => BookResource::collection($books),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/book/{id}/is_available",
     *     summary="Check if a book is available",
     *     description="Returns whether the book with the given ID has available copies",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to check availability",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Availability status",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_available", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book is not found")
     *         )
     *     )
     * )
     */
    public function is_available(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(["message" => "Book is not found"], 400);
        }
        $available = $book->available_copies > 0;
        return response()->json(["is_available" => $available], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/book/recommended-books",
     *     summary="Get top recommended books for the authenticated user",
     *     description="Returns up to 10 books based on user's most-read categories, excluding already borrowed books. Falls back to top-rated unread books.",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Recommended books retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Recommended books"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function recommended_books(Request $request)
    {
        $user = $request->user();
        $mostReadCategories = DB::table('borrows')
            ->select('books.category_id', DB::raw('COUNT(*) as borrow_count'))
            ->join('books', 'borrows.book_id', '=', 'books.id')
            ->where('borrows.user_id', $user->id)
            ->groupBy('books.category_id')
            ->orderByDesc('borrow_count')
            ->limit(5)
            ->pluck('category_id');
        $alreadyBorrowedBookIds = Borrow::where('user_id', $user->id)->pluck('book_id');

        $recommendedBooks = Book::with('category')->whereIn('category_id', $mostReadCategories)
            ->whereNotIn('id', $alreadyBorrowedBookIds)
            ->orderByDesc('average_rating')
            ->limit(10)
            ->get();
        if ($recommendedBooks->isEmpty()) {
            $recommendedBooks = Book::with('category')
                ->whereNotIn('id', $alreadyBorrowedBookIds)
                ->orderByDesc('average_rating')
                ->limit(10)
                ->get();
        }
        return response()->json([
            'message' => 'Recommended books',
            'data' => BookResource::collection($recommendedBooks),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/book/related-books/{id}",
     *     summary="Get related books by category",
     *     description="Returns up to 5 books related to the given book by category, excluding the book itself.",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the book to find related books for",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Related books retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Related books"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Book")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function relatedBooks(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(["message" => "Book not found"], 400);
        }

        $relatedBooks = Book::where('category_id', $book->category_id)
            ->where('id', '!=', $id)
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Related books',
            'data' => BookResource::collection($relatedBooks),
        ], 200);
    }
}
