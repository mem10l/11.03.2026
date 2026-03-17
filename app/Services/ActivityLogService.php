<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Log a user action.
     */
    public function logAction(
        string $action,
        ?int $userId = null,
        Model|string|null $entity = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?Request $request = null
    ): ActivityLog {
        $entityType = null;

        if ($entity instanceof Model) {
            $entityType = get_class($entity);
            $entityId = $entity->getKey();
        } elseif (is_string($entity)) {
            $entityType = $entity;
        }

        return ActivityLog::log(
            action: $action,
            userId: $userId,
            entityType: $entityType,
            entityId: $entityId,
            description: $description,
            metadata: $metadata,
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent()
        );
    }

    /**
     * Log a created action.
     */
    public function logCreated(
        Model $entity,
        ?int $userId = null,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'created',
            userId: $userId,
            entity: $entity,
            description: sprintf('Created %s with ID %d', get_class($entity), $entity->getKey()),
            metadata: $entity->toArray(),
            request: $request
        );
    }

    /**
     * Log an updated action.
     */
    public function logUpdated(
        Model $entity,
        array $changes,
        ?int $userId = null,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'updated',
            userId: $userId,
            entity: $entity,
            description: sprintf('Updated %s with ID %d', get_class($entity), $entity->getKey()),
            metadata: ['changes' => $changes],
            request: $request
        );
    }

    /**
     * Log a deleted action.
     */
    public function logDeleted(
        Model $entity,
        ?int $userId = null,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'deleted',
            userId: $userId,
            entity: $entity,
            description: sprintf('Deleted %s with ID %d', get_class($entity), $entity->getKey()),
            metadata: $entity->toArray(),
            request: $request
        );
    }

    /**
     * Log a viewed action.
     */
    public function logViewed(
        Model $entity,
        ?int $userId = null,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'viewed',
            userId: $userId,
            entity: $entity,
            description: sprintf('Viewed %s with ID %d', get_class($entity), $entity->getKey()),
            request: $request
        );
    }

    /**
     * Log a login action.
     */
    public function logLogin(
        int $userId,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'login',
            userId: $userId,
            description: 'User logged in',
            request: $request
        );
    }

    /**
     * Log a logout action.
     */
    public function logLogout(
        int $userId,
        ?Request $request = null
    ): ActivityLog {
        return $this->logAction(
            action: 'logout',
            userId: $userId,
            description: 'User logged out',
            request: $request
        );
    }

    /**
     * Get activity logs for a user.
     */
    public function getUserActivity(
        int $userId,
        ?string $action = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ) {
        $query = ActivityLog::forUser($userId);

        if ($action) {
            $query->forAction($action);
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->with('user')->latest('logged_at')->paginate($perPage);
    }

    /**
     * Get activity logs for an entity.
     */
    public function getEntityActivity(
        string $entityType,
        ?int $entityId = null,
        int $perPage = 15
    ) {
        $query = ActivityLog::forEntity($entityType, $entityId);

        return $query->with('user')->latest('logged_at')->paginate($perPage);
    }

    /**
     * Get all activity logs.
     */
    public function getAllActivity(
        ?string $action = null,
        ?int $userId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ) {
        $query = ActivityLog::with('user');

        if ($action) {
            $query->forAction($action);
        }

        if ($userId) {
            $query->forUser($userId);
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->latest('logged_at')->paginate($perPage);
    }

    /**
     * Clear old activity logs.
     */
    public function clearOldLogs(int $daysToKeep = 90): int
    {
        return ActivityLog::where('logged_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
