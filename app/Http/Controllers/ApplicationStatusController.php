<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationStatusResource;
use App\Http\Resources\ApplicationStatusResourceCollection;
use App\Models\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApplicationStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ApplicationStatusResourceCollection
    {
        $statuses = ApplicationStatus::all();

        return new ApplicationStatusResourceCollection($statuses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_name' => ['required', 'string', 'max:45', 'unique:application_statuses,status_name'],
        ]);

        $status = ApplicationStatus::create($validated);

        return response()->json([
            'message' => 'Application status created successfully.',
            'data' => new ApplicationStatusResource($status),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ApplicationStatus $applicationStatus): ApplicationStatusResource
    {
        return new ApplicationStatusResource($applicationStatus);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApplicationStatus $applicationStatus): JsonResponse
    {
        $validated = $request->validate([
            'status_name' => ['required', 'string', 'max:45', 'unique:application_statuses,status_name,' . $applicationStatus->status_id . ',status_id'],
        ]);

        $applicationStatus->update($validated);

        return response()->json([
            'message' => 'Application status updated successfully.',
            'data' => new ApplicationStatusResource($applicationStatus),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApplicationStatus $applicationStatus): JsonResponse
    {
        $applicationStatus->delete();

        return response()->json([
            'message' => 'Application status deleted successfully.',
        ]);
    }
}
