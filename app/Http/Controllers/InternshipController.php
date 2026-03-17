<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Http\Resources\InternshipResourceCollection;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InternshipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InternshipResourceCollection
    {
        $query = Internship::with(['class', 'supervisor', 'gradingType']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('supervisor_id')) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        if ($request->has('grading_type_id')) {
            $query->where('grading_type_id', $request->grading_type_id);
        }

        if ($request->has('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }

        if ($request->has('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        $internships = $query->paginate($request->get('per_page', 15));

        return new InternshipResourceCollection($internships);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'class_id' => ['required', 'exists:classes,class_id'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'grading_type_id' => ['required', 'exists:grading_types,type_id'],
        ]);

        $internship = Internship::create($validated);

        return response()->json([
            'message' => 'Internship created successfully.',
            'data' => new InternshipResource($internship->load(['class', 'supervisor', 'gradingType'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Internship $internship): InternshipResource
    {
        $internship->load(['class', 'supervisor', 'gradingType', 'applications', 'placements', 'grades']);

        return new InternshipResource($internship);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Internship $internship): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'class_id' => ['sometimes', 'required', 'exists:classes,class_id'],
            'supervisor_id' => ['sometimes', 'required', 'exists:users,id'],
            'grading_type_id' => ['sometimes', 'required', 'exists:grading_types,type_id'],
        ]);

        $internship->update($validated);

        return response()->json([
            'message' => 'Internship updated successfully.',
            'data' => new InternshipResource($internship->fresh(['class', 'supervisor', 'gradingType'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Internship $internship): JsonResponse
    {
        $internship->delete();

        return response()->json([
            'message' => 'Internship deleted successfully.',
        ]);
    }
}
