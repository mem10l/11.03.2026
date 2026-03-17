<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserRoleResource;
use App\Http\Resources\UserRoleResourceCollection;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): UserRoleResourceCollection
    {
        $roles = UserRole::all();

        return new UserRoleResourceCollection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:45', 'unique:user_roles,role_name'],
        ]);

        $role = UserRole::create($validated);

        return response()->json([
            'message' => 'User role created successfully.',
            'data' => new UserRoleResource($role),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserRole $userRole): UserRoleResource
    {
        return new UserRoleResource($userRole);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserRole $userRole): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:45', 'unique:user_roles,role_name,' . $userRole->role_id . ',role_id'],
        ]);

        $userRole->update($validated);

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => new UserRoleResource($userRole),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRole $userRole): JsonResponse
    {
        $userRole->delete();

        return response()->json([
            'message' => 'User role deleted successfully.',
        ]);
    }
}
