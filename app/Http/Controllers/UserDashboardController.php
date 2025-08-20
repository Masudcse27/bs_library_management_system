<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowResource;
use App\Models\Borrow;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/user-dashboard/statistics",
     *     summary="Get borrow statistics for authenticated user",
     *     tags={"User Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User borrow statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="statistics", type="object",
     *                 @OA\Property(property="total_borrowed_books", type="integer", example=5),
     *                 @OA\Property(property="total_returned_books", type="integer", example=3),
     *                 @OA\Property(property="total_overdue_books", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - missing or invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        

        $totalBorrowedBooks = Borrow::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status', 'borrowed')
                    ->orWhere('status', 'returned');
            })
            ->count();

        $totalReturnedBooks = Borrow::where('user_id', $user->id)
            ->where('status', 'returned')
            ->count();

        $totalOverdueBooks = Borrow::where('user_id', $user->id)
            ->where('return_date', '<', now())
            ->count();
        $totalBorrowRequest = Borrow::where('user_id', $user->id)->count();
        $totalBorrowReject = Borrow::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $statistics = [
            'total_borrowed_books' => $totalBorrowedBooks,
            'total_returned_books' => $totalReturnedBooks,
            'total_overdue_books' => $totalOverdueBooks,
            'total_borrow_request' => $totalBorrowRequest,
            'total_borrow_reject' => $totalBorrowReject,
        ];

        return response()->json(['statistics' => $statistics], 200);
    }



    /**
     * @OA\Get(
     *     path="/api/user-dashboard/borrowed-books",
     *     summary="List borrowed books for authenticated user",
     *     tags={"User Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of borrowed books",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Borrow")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - missing or invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function borrowedBooks(Request $request)
    {
        $user = $request->user();
        
        $borrowedBooks = Borrow::with('book', 'user')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->paginate(10);

        return BorrowResource::collection($borrowedBooks)->response()->setStatusCode(200);
    }
}
