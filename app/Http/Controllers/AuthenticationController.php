<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthenticationController extends Controller
{
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

            var_dump($laravelToken);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}
