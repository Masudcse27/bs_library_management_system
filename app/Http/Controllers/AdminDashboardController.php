<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowResource;
use App\Models\Book;
use App\Models\Borrow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin-dashboard/statistics",
     *     summary="Get system statistics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_borrows", type="integer", example=150),
     *             @OA\Property(property="total_returns", type="integer", example=120),
     *             @OA\Property(property="overdue_borrows", type="integer", example=15),
     *             @OA\Property(property="current_total_books", type="integer", example=300),
     *             @OA\Property(property="new_members", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $totalBorrows = Borrow::count();
        $totalReturns = Borrow::where('status', 'returned')->count();
        $overDueBorrows = Borrow::where('status', 'borrowed')
            ->where('return_date', '<', now())
            ->count();
        $currentTotalBooks = Book::count();
        $newMembers = User::where('created_at', '>=', now()->subMonth())->count();

        return response()->json([
            'total_borrows' => $totalBorrows,
            'total_returns' => $totalReturns,
            'overdue_borrows' => $overDueBorrows,
            'current_total_books' => $currentTotalBooks,
            'new_members' => $newMembers,
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/api/admin-dashboard/borrows-chart",
     *     summary="Get number of borrows per day for the last 7 days",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with daily borrow counts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="day", type="string", example="Monday"),
     *                 @OA\Property(property="total", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function borrows_chart()
    {
        $borrowData = DB::table('borrows')
            ->select(DB::raw('DATE(borrowed_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('borrowed_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(borrowed_at)'))
            ->pluck('total', 'date');

        $result = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result->push([
                'day' => Carbon::parse($date)->format('l'),
                'total' => $borrowData[$date] ?? 0,
            ]);
        }
        return response()->json($result, 200);
    }
    /**
     * @OA\Get(
     *     path="/api/admin-dashboard/recent-borrows",
     *     summary="Get recent borrows in the last 7 days (paginated)",
     *     description="Returns a paginated list of recent borrows within the past week.",
     *     operationId="getRecentBorrows",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful paginated response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Borrow")),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string", example="http://yourdomain.com/api/borrows/recent?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://yourdomain.com/api/borrows/recent?page=5"),
     *                 @OA\Property(property="prev", type="string", nullable=true, example=null),
     *                 @OA\Property(property="next", type="string", nullable=true, example="http://yourdomain.com/api/borrows/recent?page=2")
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="path", type="string", example="http://yourdomain.com/api/borrows/recent"),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function recent_borrows(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $recentBorrows = Borrow::with('book','user')->where('status', 'borrowed')
            ->where('borrowed_at', '>=', now()->subWeek())
            ->paginate($perPage);
        return (BorrowResource::collection($recentBorrows))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin-dashboard/overdue-borrows",
     *     summary="Get list of overdue borrowed books",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Overdue borrowed books list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Borrow")),
     *         )
     *     )
     * )
     */
    public function overdue_borrows(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $overdueBorrows = Borrow::with('book','user')->where('status', 'borrowed')
            ->where('return_date', '<', now())
            ->paginate($perPage);
        return (BorrowResource::collection($overdueBorrows))
            ->response()
            ->setStatusCode(200);
    }

}
