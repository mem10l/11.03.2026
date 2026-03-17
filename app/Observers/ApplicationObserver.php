<?php

namespace App\Observers;

use App\Models\Application;
use App\Services\ActivityLogService;

class ApplicationObserver
{
    /**
     * Create a new observer instance.
     */
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Handle the Application "created" event.
     * 
     * This acts as a trigger alternative - logs activity when a new
     * internship application is successfully created.
     */
    public function created(Application $application): void
    {
        $this->activityLogService->logAction(
            action: 'created',
            userId: $application->student_id,
            entity: $application,
            description: sprintf(
                'Izveidots jauns prakses pieteikums internship_id: %d, company_id: %d',
                $application->internship_id,
                $application->company_id
            ),
            metadata: [
                'application_id' => $application->application_id,
                'internship_id' => $application->internship_id,
                'student_id' => $application->student_id,
                'company_id' => $application->company_id,
                'status_id' => $application->status_id,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
            ]
        );
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        $this->activityLogService->logAction(
            action: 'updated',
            userId: $application->student_id,
            entity: $application,
            description: sprintf(
                'Atjaunināts prakses pieteikums ID: %d',
                $application->application_id
            ),
            metadata: $application->getChanges()
        );
    }

    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $application): void
    {
        $this->activityLogService->logAction(
            action: 'deleted',
            userId: $application->student_id,
            entity: $application,
            description: sprintf(
                'Dzēsts prakses pieteikums ID: %d',
                $application->application_id
            ),
            metadata: [
                'application_id' => $application->application_id,
                'internship_id' => $application->internship_id,
                'student_id' => $application->student_id,
                'company_id' => $application->company_id,
            ]
        );
    }
}
