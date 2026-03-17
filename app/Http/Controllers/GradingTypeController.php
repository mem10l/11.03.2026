<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradingTypeResource;
use App\Http\Resources\GradingTypeResourceCollection;
use App\Models\GradingType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GradingTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): GradingTypeResourceCollection
    {
        $gradingTypes = GradingType::all();

        return new GradingTypeResourceCollection($gradingTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_name' => ['required', 'string', 'max:45', 'unique:grading_types,type_name'],
        ]);

        $gradingType = GradingType::create($validated);

        return response()->json([
            'message' => 'Grading type created successfully.',
            'data' => new GradingTypeResource($gradingType),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GradingType $gradingType): GradingTypeResource
    {
        return new GradingTypeResource($gradingType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GradingType $gradingType): JsonResponse
    {
        $validated = $request->validate([
            'type_name' => ['required', 'string', 'max:45', 'unique:grading_types,type_name,' . $gradingType->type_id . ',type_id'],
        ]);

        $gradingType->update($validated);

        return response()->json([
            'message' => 'Grading type updated successfully.',
            'data' => new GradingTypeResource($gradingType),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GradingType $gradingType): JsonResponse
    {
        $gradingType->delete();

        return response()->json([
            'message' => 'Grading type deleted successfully.',
        ]);
    }
}
