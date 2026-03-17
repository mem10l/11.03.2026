<?php

namespace App\Http\Controllers;

use App\Http\Resources\SchoolClassResource;
use App\Http\Resources\SchoolClassResourceCollection;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): SchoolClassResourceCollection
    {
        $query = SchoolClass::with('students');

        if ($request->has('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $classes = $query->paginate($request->get('per_page', 15));

        return new SchoolClassResourceCollection($classes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:45', 'unique:classes,class_name'],
            'school_year' => ['required', 'integer'],
        ]);

        $class = SchoolClass::create($validated);

        return response()->json([
            'message' => 'Class created successfully.',
            'data' => new SchoolClassResource($class),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SchoolClass $schoolClass): SchoolClassResource
    {
        $schoolClass->load('students', 'internships', 'classMembers');

        return new SchoolClassResource($schoolClass);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolClass $schoolClass): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:45', 'unique:classes,class_name,' . $schoolClass->class_id . ',class_id'],
            'school_year' => ['required', 'integer'],
        ]);

        $schoolClass->update($validated);

        return response()->json([
            'message' => 'Class updated successfully.',
            'data' => new SchoolClassResource($schoolClass),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolClass $schoolClass): JsonResponse
    {
        $schoolClass->delete();

        return response()->json([
            'message' => 'Class deleted successfully.',
        ]);
    }
}
