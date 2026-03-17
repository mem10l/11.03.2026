<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Http\Resources\ApplicationResourceCollection;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ApplicationResourceCollection
    {
        $query = Application::with(['internship', 'student', 'company', 'status']);

        if ($request->has('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        $applications = $query->paginate($request->get('per_page', 15));

        return new ApplicationResourceCollection($applications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ApplicationService $applicationService): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['required', 'exists:internships,internship_id'],
            'student_id' => ['required', 'exists:users,id'],
            'company_id' => ['required', 'exists:companies,company_id'],
            'motivation_letter' => ['nullable', 'string'],
        ]);

        try {
            $application = $applicationService->createApplication(
                $validated['internship_id'],
                $validated['student_id'],
                $validated['company_id'],
                $validated['motivation_letter'] ?? null
            );

            return response()->json([
                'message' => 'Prakses pieteikums izveidots veiksmīgi.',
                'data' => new ApplicationResource($application),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Kļūda izveidojot pieteikumu.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store a newly created resource using stored procedure.
     */
    public function storeWithProcedure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['required', 'exists:internships,internship_id'],
            'student_id' => ['required', 'exists:users,id'],
            'company_id' => ['required', 'exists:companies,company_id'],
            'motivation_letter' => ['nullable', 'string'],
        ]);

        try {
            $result = DB::select('
                CALL create_application(?, ?, ?, ?, @p_application_id, @p_error_message)
            ', [
                $validated['internship_id'],
                $validated['student_id'],
                $validated['company_id'],
                $validated['motivation_letter'] ?? null,
            ]);

            $outParams = DB::select('SELECT @p_application_id AS application_id, @p_error_message AS error_message');

            if ($outParams[0]->error_message !== null) {
                return response()->json([
                    'message' => 'Kļūda izveidojot pieteikumu.',
                    'error' => $outParams[0]->error_message,
                ], 422);
            }

            $application = Application::with(['internship', 'student', 'company', 'status'])
                ->find($outParams[0]->application_id);

            return response()->json([
                'message' => 'Prakses pieteikums izveidots veiksmīgi (izmantojot procedūru).',
                'data' => new ApplicationResource($application),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Kļūda izveidojot pieteikumu.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application): ApplicationResource
    {
        $application->load(['internship', 'student', 'company', 'status']);

        return new ApplicationResource($application);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'internship_id' => ['sometimes', 'required', 'exists:internships,internship_id'],
            'student_id' => ['sometimes', 'required', 'exists:users,id'],
            'company_id' => ['sometimes', 'required', 'exists:companies,company_id'],
            'status_id' => ['sometimes', 'required', 'exists:application_statuses,status_id'],
            'motivation_letter' => ['nullable', 'string'],
        ]);

        $application->update($validated);

        return response()->json([
            'message' => 'Application updated successfully.',
            'data' => new ApplicationResource($application->fresh(['internship', 'student', 'company', 'status'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application): JsonResponse
    {
        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully.',
        ]);
    }
}
