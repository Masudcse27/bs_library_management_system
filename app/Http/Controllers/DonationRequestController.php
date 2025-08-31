<?php

namespace App\Http\Controllers;

use App\Http\Resources\DonationRequestResource;
use App\Models\DonationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DonationRequestController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/donation/create",
     *     summary="Create a new donation request",
     *     description="Creates a new donation request. Authenticated users only.",
     *     operationId="createDonationRequest",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"book_title", "number_of_copies", "bs_id", "sbu"},
     *             @OA\Property(property="book_title", type="string", example="Clean Code"),
     *             @OA\Property(property="number_of_copies", type="integer", example=2),
     *             @OA\Property(property="bs_id", type="string", example="BS123"),
     *             @OA\Property(property="sbu", type="string", example="SBU456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Donation request created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DonationRequest")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
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
    public function create(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'book_title' => 'required|string|max:255',
            'number_of_copies' => 'required|integer|min:1',
            'bs_id' => 'required|string|max:255',
            'sbu' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $donationRequest = DonationRequest::create([
            'user_id' => $user->id,
            'book_title' => $request->book_title,
            'number_of_copies' => $request->number_of_copies,
            'bs_id' => $request->bs_id,
            'sbu' => $request->sbu,
            'status' => 'pending',
        ]);

        return response()->json(new DonationRequestResource($donationRequest), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/donation/list",
     *     summary="List donation requests",
     *     description="Admins see only pending requests. Other users see their own donation requests.",
     *     operationId="listDonationRequests",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of donation requests",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/DonationRequest")
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
        $user = $request->user();

        if ($user->role === 'admin') {
            $donations = DonationRequest::with('user')->paginate(10);

        } else {
            $donations = DonationRequest::with('user')->where('user_id', $user->id)->paginate(3);
        }

        return DonationRequestResource::collection($donations)
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/donation/pending",
     *     summary="List pending donation requests",
     *     description="Admins see all pending donation requests. Users see only their own pending requests.",
     *     operationId="pendingDonations",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of pending donation requests",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DonationRequest")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=45)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function pendingDonations(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $donations = DonationRequest::with('user')->where('status', 'pending')->paginate(2);

        } else {
            $donations = DonationRequest::with('user')->where('user_id', $user->id)->where('status', 'pending')->paginate(2);
        }

        return DonationRequestResource::collection($donations)
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/donation/collected",
     *     summary="List collected donation requests",
     *     description="Admins see all collected donation requests. Users see only their own collected requests.",
     *     operationId="collectedDonations",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="bs_id",
     *         in="query",
     *         description="Filter donations by bs_id",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of collected donation requests",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DonationRequest")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function collectedDonations(Request $request)
    {
        $user = $request->user();
        $perPage = $request->query('per_page', 10);

        $query = DonationRequest::with('user');

        // optional bs_id filter
        if ($request->has('bs_id')) {
            $query->where('bs_id', $request->query('bs_id'));
        }

        // role-based restriction
        if ($user->role === 'admin') {
            $query->where('status', 'collected');
        } else {
            $query->where('user_id', $user->id)
                ->where('status', 'collected');
        }

        $donations = $query->paginate($perPage);

        return DonationRequestResource::collection($donations)
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @OA\Get(
     *     path="/api/donation/retrieve/{id}",
     *     summary="Retrieve a specific donation request",
     *     description="Admins can retrieve any donation request. Users can retrieve only their own.",
     *     operationId="getDonationRequestById",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the donation request",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation request details",
     *         @OA\JsonContent(ref="#/components/schemas/DonationRequest")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function retrieve(Request $request, $id)
    {
        $donation = DonationRequest::with('user')->find($id);

        if (!$donation) {
            return response()->json(['message' => 'Donation request not found'], 404);
        }
        $user = $request->user();
        if ($user->role != 'admin' && $donation->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }
        return response()->json(new DonationRequestResource($donation), 200);

    }

    /**
     * @OA\Put(
     *     path="/api/donation/edit/{id}",
     *     summary="Update a donation request",
     *     description="Only the user who created the donation request can update it.",
     *     operationId="updateDonationRequest",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the donation request",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="book_title", type="string", example="Introduction to Algorithms"),
     *             @OA\Property(property="author_name", type="string", example="Thomas H. Cormen"),
     *             @OA\Property(property="number_of_copies", type="integer", example=2),
     *             @OA\Property(property="location", type="string", example="Main Library"),
     *             @OA\Property(property="contact_number", type="string", example="0123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation request updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DonationRequest")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation request not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $donation = DonationRequest::find($id);

        if (!$donation) {
            return response()->json(['message' => 'Donation request not found'], 404);
        }

        $user = $request->user();

        if ($donation->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }
        $validator = Validator::make($request->all(), [
            'book_title' => 'required|string|max:255',
            'number_of_copies' => 'required|integer|min:1',
            'bs_id' => 'required|string|max:255',
            'sbu' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $donation->update($request->only([
            'book_title',
            'bs_id',
            'number_of_copies',
            'sbu',
        ]));

        return response()->json(new DonationRequestResource($donation), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/donation/delete/{id}",
     *     summary="Delete a donation request",
     *     description="Only the user who created the donation request can delete it.",
     *     operationId="deleteDonationRequest",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the donation request",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation request deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Donation request deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function delete(Request $request, $id)
    {
        $donation = DonationRequest::find($id);

        if (!$donation) {
            return response()->json(['message' => 'Donation request not found'], 404);
        }

        $user = $request->user();

        if ($donation->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        if ($donation->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved donation requests'], 403);
        }
        $donation->delete();

        return response()->json(['message' => 'Donation request deleted successfully'], 200);
    }

    /**
     * @OA\get(
     *     path="/api/donation/collect/{id}",
     *     summary="Collect a donation request",
     *     description="Only admin users can collect donation requests.",
     *     operationId="collectDonationRequest",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the donation request",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Donation request status updated",
     *         @OA\JsonContent(ref="#/components/schemas/DonationRequest")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid action"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function collect(Request $request, $id)
    {
        $donation = DonationRequest::find($id);

        if (!$donation) {
            return response()->json(['message' => 'Donation request not found'], 404);
        }

        $user = $request->user();

        if ($user->role != 'admin') {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $donation->status = 'collected';
        $donation->save();

        return response()->json(new DonationRequestResource($donation), 202);
    }
}
