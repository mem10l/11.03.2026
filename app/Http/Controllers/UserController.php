<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): UserResourceCollection
    {
        $query = User::with('role');

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return new UserResourceCollection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:45'],
            'last_name' => ['required', 'string', 'max:45'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'role_id' => ['required', 'exists:user_roles,role_id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user->load('role')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        $user->load('role', 'classes', 'applications', 'placements', 'grades');

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:45'],
            'last_name' => ['sometimes', 'required', 'string', 'max:45'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:100', 'unique:users,email,'.$user->id.',id'],
            'role_id' => ['sometimes', 'required', 'exists:user_roles,role_id'],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($user->fresh('role')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Get students by class ID.
     */
    public function studentsByClass(Request $request, int $classId): UserResourceCollection
    {
        $query = User::with('classes')
            ->where('role_id', 3) // Only students
            ->whereHas('classes', function ($q) use ($classId) {
                $q->where('classes.class_id', $classId);
            });

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate($request->get('per_page', 15));

        return new UserResourceCollection($students);
    }
}
