<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthenticationController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/login",
     *     summary="Login using Moodle JWT token",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Moodle-issued JWT token",
     *         required=true,
     *         @OA\Schema(type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laravel JWT token issued",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJh...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User not found in Laravel",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found in Laravel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid token"),
     *             @OA\Property(property="message", type="string", example="Signature verification failed")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $token = $request->query('token');
        $secret = 'your_laravel_jwt_secret_here'; // same as used in Moodle

        try {
            // Decode Moodle JWT
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $payload = (array) $decoded;

            // Find Laravel user by email
            $user = User::where('email', $payload['email'])->first();
            if (!$user) {
                return response()->json(['error' => 'User not found in Laravel'], 400);
            }

            // Authenticate user with Tymon JWT and generate a Laravel token
            JWTAuth::factory()->setTTL(1440);  // set token expiration to 1440 minutes (1 day)
            $laravelToken = JWTAuth::fromUser($user);
            $parsed = parse_url(url('/'));
            $hostOnly = $parsed['scheme'] . '://' . $parsed['host']; // only scheme + host
            $frontendUrl = $hostOnly . ':8001/auth/callback'; // set your own port here
            return redirect()->away($frontendUrl . '?token=' . $laravelToken);

            // var_dump($laravelToken);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => $e->getMessage(),
            ], 401);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get authenticated user data",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-13T12:34:56Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-13T12:34:56Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function userData(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to log out",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to log out")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to log out'], 500);
        }
    }
}
