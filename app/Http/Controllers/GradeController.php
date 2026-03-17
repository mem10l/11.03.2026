<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradeResource;
use App\Http\Resources\GradeResourceCollection;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): GradeResourceCollection
    {
        $query = Grade::with(['internship', 'student']);

        if ($request->has('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $grades = $query->paginate($request->get('per_page', 15));

        return new GradeResourceCollection($grades);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['required', 'exists:internships,internship_id'],
            'student_id' => ['required', 'exists:users,id'],
            'grade' => ['required', 'string', 'max:20'],
            'comment' => ['nullable', 'string'],
        ]);

        $grade = Grade::create($validated);

        return response()->json([
            'message' => 'Grade created successfully.',
            'data' => new GradeResource($grade->load(['internship', 'student'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Grade $grade): GradeResource
    {
        $grade->load(['internship', 'student']);

        return new GradeResource($grade);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grade $grade): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['sometimes', 'required', 'exists:internships,internship_id'],
            'student_id' => ['sometimes', 'required', 'exists:users,id'],
            'grade' => ['sometimes', 'required', 'string', 'max:20'],
            'comment' => ['nullable', 'string'],
        ]);

        $grade->update($validated);

        return response()->json([
            'message' => 'Grade updated successfully.',
            'data' => new GradeResource($grade->fresh(['internship', 'student'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade): JsonResponse
    {
        $grade->delete();

        return response()->json([
            'message' => 'Grade deleted successfully.',
        ]);
    }
}
