<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * List all users with pagination.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Optional search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Optional filter by admin status
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json($users);
    }

    /**
     * Get a single user by ID.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json(['user' => $user]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'is_admin' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin', false),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8',
            'is_admin' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->has('is_admin')) {
            // Prevent admin from removing their own admin status
            if ($request->user()->id == $user->id && !$request->boolean('is_admin')) {
                return response()->json(['message' => 'You cannot remove your own admin status'], 422);
            }
            $updateData['is_admin'] = $request->boolean('is_admin');
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from deleting themselves
        if ($request->user()->id == $user->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Reset a user's password (admin action).
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Generate a random password
        $newPassword = Str::password(12); // Laravel 10+ has Str::password

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
            'new_password' => $newPassword, // In production, consider sending via email only
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Toggle admin status for a user.
     */
    public function assignAdmin(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from removing their own admin status
        if ($request->user()->id == $user->id && $user->is_admin) {
            return response()->json(['message' => 'You cannot remove your own admin status'], 422);
        }

        $user->update([
            'is_admin' => !$user->is_admin,
        ]);

        return response()->json([
            'message' => 'Admin status updated successfully',
            'user' => $user->fresh(),
        ]);
    }
}