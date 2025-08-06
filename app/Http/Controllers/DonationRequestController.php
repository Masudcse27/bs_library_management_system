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
     *             required={"book_title", "number_of_copies", "contact_number"},
     *             @OA\Property(property="book_title", type="string", example="Clean Code"),
     *             @OA\Property(property="author_name", type="string", example="Robert C. Martin"),
     *             @OA\Property(property="number_of_copies", type="integer", example=2),
     *             @OA\Property(property="location", type="string", example="Chittagong Central Library"),
     *             @OA\Property(property="contact_number", type="string", example="+8801771234567")
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
            'author_name' => 'nullable|string|max:255',
            'number_of_copies' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $donationRequest = DonationRequest::create([
            'user_id' => $user->id,
            'book_title' => $request->book_title,
            'author_name' => $request->author_name,
            'number_of_copies' => $request->number_of_copies,
            'location' => $request->location,
            'contact_number' => $request->contact_number,
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
            $donations = DonationRequest::with('user')->where('status', 'pending')->get();
        } else {
            $donations = DonationRequest::with('user')->where('user_id', $user->id)->get();
        }

        return response()->json(DonationRequestResource::collection($donations), 200);
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
            'book_title' => 'sometimes|required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'number_of_copies' => 'sometimes|required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'contact_number' => 'sometimes|required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $donation->update($request->only([
            'book_title',
            'author_name',
            'number_of_copies',
            'location',
            'contact_number'
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

        if($donation->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved donation requests'], 403);
        }
        $donation->delete();

        return response()->json(['message' => 'Donation request deleted successfully'], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/donation/approve-reject/{id}",
     *     summary="Approve or reject a donation request",
     *     description="Only admin users can approve or reject donation requests.",
     *     operationId="approveRejectDonationRequest",
     *     tags={"Donation Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the donation request",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"approve", "reject"}, example="approve")
     *         )
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
    public function approve_reject(Request $request, $id)
    {
        $donation = DonationRequest::find($id);

        if (!$donation) {
            return response()->json(['message' => 'Donation request not found'], 404);
        }

        $user = $request->user();

        if ($user->role != 'admin') {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $action = $request->input('action');

        if (!in_array($action, ['approve', 'reject'])) {
            return response()->json(['message' => 'Invalid action'], 400);
        }

        $donation->status = $action == 'approve' ? 'approved' : 'rejected';
        $donation->save();

        return response()->json(new DonationRequestResource($donation), 202);
    }
}
