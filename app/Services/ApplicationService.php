<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ApplicationService
{
    /**
     * Create a new internship application with validations.
     *
     * @throws Exception
     */
    public function createApplication(
        int $internshipId,
        int $studentId,
        int $companyId,
        ?string $motivationLetter = null
    ): Application {
        // Perform validations outside transaction and log failures
        // a) Pārbauda, vai lietotājs eksistē datubāzē
        $student = $this->validateUserExists($studentId);

        // b) Pārbauda, vai prakse ir derīga
        $internship = $this->validateInternshipIsValid($internshipId);

        // c) Pārbauda, vai lietotājam ir atļauts pieteikties šajā praksē
        $this->validateUserCanApply($student, $internship);

        // d) Pārbauda, vai ir iesniegts motivācijas vēstule
        $this->validateMotivationLetter(motivationLetter: $motivationLetter);

        // All validations passed, now create the application in a transaction
        return DB::transaction(function () use ($internshipId, $studentId, $companyId, $motivationLetter, $student, $internship) {
            // Re-validate within transaction to ensure data integrity
            $this->validateInternshipIsValid($internshipId, logFailure: false);
            $this->validateUserCanApply($student, $internship, logFailure: false);

            // Izveido prakses pieteikumu
            $application = Application::create([
                'internship_id' => $internshipId,
                'student_id' => $studentId,
                'company_id' => $companyId,
                'status_id' => 1, // Noklusējuma statuss (piemērots)
                'motivation_letter' => $motivationLetter,
                'submitted_at' => now(),
            ]);

            return $application->fresh(['internship', 'student', 'company', 'status']);
        });
    }

    /**
     * Validate that the user exists in the database.
     *
     * @throws Exception
     */
    private function validateUserExists(int $userId, bool $logFailure = true): User
    {
        $user = User::find($userId);

        if (! $user) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    null,
                    'Lietotājs nav atrasts datubāzē.',
                    [
                        'student_id' => $userId,
                        'failure_reason' => 'Lietotājs nav atrasts datubāzē.',
                    ]
                );
            }
            throw new Exception('Lietotājs netika atrasts datubāzē.');
        }

        return $user;
    }

    /**
     * Validate that the internship is valid (exists and dates are correct).
     *
     * @throws Exception
     */
    private function validateInternshipIsValid(int $internshipId, bool $logFailure = true): Internship
    {
        $internship = Internship::with('class')->find($internshipId);

        if (! $internship) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    null,
                    'Prakse nav atrasta.',
                    [
                        'internship_id' => $internshipId,
                        'failure_reason' => 'Prakse nav atrasta.',
                    ]
                );
            }
            throw new Exception('Prakse netika atrasta.');
        }

        // Pārbauda, vai prakses datumi ir derīgi
        if ($internship->start_date > $internship->end_date) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    null,
                    'Prakses datumi nav derīgi.',
                    [
                        'internship_id' => $internshipId,
                        'start_date' => $internship->start_date,
                        'end_date' => $internship->end_date,
                        'failure_reason' => 'Prakses sākuma datums ir vēlāks par beigu datumu.',
                    ]
                );
            }
            throw new Exception('Prakses sākuma datums ir vēlāks par beigu datumu.');
        }

        // Pārbauda, vai prakse vēl nav beigusies
        if ($internship->end_date < now()) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    null,
                    'Prakse ir beigusies.',
                    [
                        'internship_id' => $internshipId,
                        'internship_end_date' => $internship->end_date,
                        'failure_reason' => 'Prakse ir beigusies.',
                    ]
                );
            }
            throw new Exception('Prakse ir beigusies.');
        }

        return $internship;
    }

    /**
     * Validate that the user is allowed to apply for this internship.
     *
     * @throws Exception
     */
    private function validateUserCanApply(User $student, Internship $internship, bool $logFailure = true): void
    {
        // Pārbauda, vai students ir klasē, kurai paredzēta prakse
        $isClassMember = $student->classes()
            ->where('classes.class_id', $internship->class_id)
            ->exists();

        if (! $isClassMember) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    $student->id,
                    'Studentam nav atļauts pieteikties šajā praksē.',
                    [
                        'student_id' => $student->id,
                        'internship_id' => $internship->internship_id,
                        'class_id' => $internship->class_id,
                        'failure_reason' => 'Nav attiecīgās klases biedrs.',
                    ]
                );
            }
            throw new Exception('Studentam nav atļauts pieteikties šajā praksē - nav attiecīgās klases biedrs.');
        }

        // Pārbauda, vai students jau nav pieteicies šai praksei
        $existingApplication = Application::where('internship_id', $internship->internship_id)
            ->where('student_id', $student->id)
            ->exists();

        if ($existingApplication) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    $student->id,
                    'Students jau ir pieteicies šai praksei.',
                    [
                        'student_id' => $student->id,
                        'internship_id' => $internship->internship_id,
                        'failure_reason' => 'Jau eksistē pieteikums.',
                    ]
                );
            }
            throw new Exception('Students jau ir pieteicies šai praksei.');
        }
    }

    /**
     * Validate that the motivation letter is provided.
     *
     * @throws Exception
     */
    private function validateMotivationLetter(bool $logFailure = true, ?string $motivationLetter = null): void
    {
        if (empty(trim($motivationLetter ?? ''))) {
            if ($logFailure) {
                $this->logFailedApplicationAttempt(
                    'application_failed',
                    null,
                    'Motivācijas vēstule trūkst.',
                    [
                        'failure_reason' => 'Motivācijas vēstule ir obligāta.',
                    ]
                );
            }
            throw new Exception('Motivācijas vēstule ir obligāta.');
        }
    }

    /**
     * Log a failed application attempt to the activity_logs table.
     */
    private function logFailedApplicationAttempt(
        string $action,
        ?int $userId,
        string $description,
        array $metadata = []
    ): void {
        ActivityLog::log(
            action: $action,
            userId: $userId,
            entityType: Application::class,
            entityId: null,
            description: $description,
            metadata: $metadata,
            ipAddress: Request::ip(),
            userAgent: Request::userAgent()
        );
    }
}
