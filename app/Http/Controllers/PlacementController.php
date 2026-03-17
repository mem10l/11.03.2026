<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlacementResource;
use App\Http\Resources\PlacementResourceCollection;
use App\Models\Placement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlacementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): PlacementResourceCollection
    {
        $query = Placement::with(['internship', 'student', 'company']);

        if ($request->has('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $placements = $query->paginate($request->get('per_page', 15));

        return new PlacementResourceCollection($placements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['required', 'exists:internships,internship_id'],
            'student_id' => ['required', 'exists:users,id'],
            'company_id' => ['required', 'exists:companies,company_id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        $placement = Placement::create($validated);

        return response()->json([
            'message' => 'Placement created successfully.',
            'data' => new PlacementResource($placement->load(['internship', 'student', 'company'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Placement $placement): PlacementResource
    {
        $placement->load(['internship', 'student', 'company']);

        return new PlacementResource($placement);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Placement $placement): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['sometimes', 'required', 'exists:internships,internship_id'],
            'student_id' => ['sometimes', 'required', 'exists:users,id'],
            'company_id' => ['sometimes', 'required', 'exists:companies,company_id'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        $placement->update($validated);

        return response()->json([
            'message' => 'Placement updated successfully.',
            'data' => new PlacementResource($placement->fresh(['internship', 'student', 'company'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Placement $placement): JsonResponse
    {
        $placement->delete();

        return response()->json([
            'message' => 'Placement deleted successfully.',
        ]);
    }
}
