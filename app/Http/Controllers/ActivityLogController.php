<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\ActivityLogResourceCollection;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of all activity logs.
     */
    public function index(Request $request, ActivityLogService $activityLogService): ActivityLogResourceCollection
    {
        $logs = $activityLogService->getAllActivity(
            action: $request->get('action'),
            userId: $request->get('user_id'),
            startDate: $request->get('start_date'),
            endDate: $request->get('end_date'),
            perPage: $request->get('per_page', 15)
        );

        return new ActivityLogResourceCollection($logs);
    }

    /**
     * Display activity logs for a specific user.
     */
    public function userActivity(int $userId, Request $request, ActivityLogService $activityLogService): ActivityLogResourceCollection
    {
        $logs = $activityLogService->getUserActivity(
            userId: $userId,
            action: $request->get('action'),
            startDate: $request->get('start_date'),
            endDate: $request->get('end_date'),
            perPage: $request->get('per_page', 15)
        );

        return new ActivityLogResourceCollection($logs);
    }

    /**
     * Display activity logs for a specific entity.
     */
    public function entityActivity(Request $request, ActivityLogService $activityLogService): ActivityLogResourceCollection
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['nullable', 'integer'],
        ]);

        $logs = $activityLogService->getEntityActivity(
            entityType: $validated['entity_type'],
            entityId: $validated['entity_id'] ?? null,
            perPage: $request->get('per_page', 15)
        );

        return new ActivityLogResourceCollection($logs);
    }

    /**
     * Display the specified activity log.
     */
    public function show(int $logId): ActivityLogResource
    {
        $log = ActivityLog::with('user')->findOrFail($logId);

        return new ActivityLogResource($log);
    }

    /**
     * Store a new activity log entry.
     */
    public function store(Request $request, ActivityLogService $activityLogService): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $entityType = null;
        if (isset($validated['entity_type'])) {
            $entityType = $validated['entity_type'];
        }

        $log = $activityLogService->logAction(
            action: $validated['action'],
            userId: $validated['user_id'] ?? null,
            entity: $entityType,
            entityId: $validated['entity_id'] ?? null,
            description: $validated['description'] ?? null,
            metadata: $validated['metadata'] ?? null,
            request: $request
        );

        return response()->json([
            'message' => 'Activity log created successfully.',
            'data' => new ActivityLogResource($log->load('user')),
        ], 201);
    }

    /**
     * Clear old activity logs.
     */
    public function clearOld(Request $request, ActivityLogService $activityLogService): JsonResponse
    {
        $validated = $request->validate([
            'days_to_keep' => ['sometimes', 'integer', 'min:1', 'max:3650'],
        ]);

        $deletedCount = $activityLogService->clearOldLogs($validated['days_to_keep'] ?? 90);

        return response()->json([
            'message' => sprintf('Cleared %d old activity logs.', $deletedCount),
            'deleted_count' => $deletedCount,
        ]);
    }
}
