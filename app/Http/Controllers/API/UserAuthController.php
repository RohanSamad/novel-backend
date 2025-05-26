<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserAuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:profiles,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        try {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $profile = Profile::create([
                'id' => $user->id,
                'username' => $validated['username'],
                'role' => 'user',
                'last_sign_in_at' => Carbon::now(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $profile = $user->profile;
        if (!$profile) {
            $username = explode('@', $user->email)[0];
            while (Profile::where('username', $username)->exists()) {
                $username = explode('@', $user->email)[0] . '_' . rand(1000, 9999);
            }
            $profile = Profile::create([
                'id' => $user->id,
                'username' => $username,
                'role' => 'user',
                'last_sign_in_at' => Carbon::now(),
            ]);
        } else {
            $profile->update(['last_sign_in_at' => Carbon::now()]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    /**
     * Check current session
     */
    public function checkSession(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                \Log::info('No authenticated user found for checkSession');
                return response()->json(null, 200);
            }

            $profile = $user->profile;
            if (!$profile) {
                $username = explode('@', $user->email)[0];
                while (Profile::where('username', $username)->exists()) {
                    $username = explode('@', $user->email)[0] . '_' . rand(1000, 9999);
                }
                $profile = Profile::create([
                    'id' => $user->id,
                    'username' => $username,
                    'role' => 'user',
                    'last_sign_in_at' => Carbon::now(),
                ]);
            } else {
                $profile->update(['last_sign_in_at' => Carbon::now()]);
            }

            return new UserResource($user);
        } catch (\Exception $e) {
            \Log::error('Session check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(null, 200);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * Fetch all users (admin only)
     */
    public function fetchUsers(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->profile || $user->profile->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::with('profile')->orderBy('created_at', 'desc')->get();

        return new UserCollection($users);
    }

    /**
     * Update user role (admin only)
     */
    public function updateUserRole(Request $request)
    {
        \Log::debug('Request Data:', $request->all());
        $user = Auth::user();
        if (!$user || !$user->profile || $user->profile->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id',
            'role' => 'required|in:user,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $profile = Profile::where('id', $request->userId)->first();
        if (!$profile) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        $profile->update(['role' => $request->role]);

        return response()->json([
            'userId' => $request->userId,
            'role' => $request->role,
        ], 200);
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->profile || $user->profile->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $targetUser = User::find($request->userId);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $targetUser->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'userId' => $request->userId,
        ], 200);
    }
}