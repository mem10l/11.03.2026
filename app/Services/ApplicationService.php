<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use Exception;

class ApplicationService
{
    /**
     * Create a new internship application with validations.
     *
     * @param int $internshipId
     * @param int $studentId
     * @param int $companyId
     * @param string|null $motivationLetter
     * @return Application
     * @throws Exception
     */
    public function createApplication(
        int $internshipId,
        int $studentId,
        int $companyId,
        ?string $motivationLetter = null
    ): Application {
        return DB::transaction(function () use ($internshipId, $studentId, $companyId, $motivationLetter) {
            // a) Pārbauda, vai lietotājs eksistē datubāzē
            $student = $this->validateUserExists($studentId);

            // b) Pārbauda, vai prakse ir derīga
            $internship = $this->validateInternshipIsValid($internshipId);

            // c) Pārbauda, vai lietotājam ir atļauts pieteikties šajā praksē
            $this->validateUserCanApply($student, $internship);

            // d) Pārbauda, vai ir iesniegts motivācijas vēstule
            $this->validateMotivationLetter($motivationLetter);

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
     * @param int $userId
     * @return User
     * @throws Exception
     */
    private function validateUserExists(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new Exception('Lietotājs netika atrasts datubāzē.');
        }

        return $user;
    }

    /**
     * Validate that the internship is valid (exists and dates are correct).
     *
     * @param int $internshipId
     * @return Internship
     * @throws Exception
     */
    private function validateInternshipIsValid(int $internshipId): Internship
    {
        $internship = Internship::with('class')->find($internshipId);

        if (!$internship) {
            throw new Exception('Prakse netika atrasta.');
        }

        // Pārbauda, vai prakses datumi ir derīgi
        if ($internship->start_date > $internship->end_date) {
            throw new Exception('Prakses sākuma datums ir vēlāks par beigu datumu.');
        }

        // Pārbauda, vai prakse vēl nav beigusies
        if ($internship->end_date < now()) {
            throw new Exception('Prakse ir beigusies.');
        }

        return $internship;
    }

    /**
     * Validate that the user is allowed to apply for this internship.
     *
     * @param User $student
     * @param Internship $internship
     * @return void
     * @throws Exception
     */
    private function validateUserCanApply(User $student, Internship $internship): void
    {
        // Pārbauda, vai students ir klasē, kurai paredzēta prakse
        $isClassMember = $student->classes()
            ->where('classes.class_id', $internship->class_id)
            ->exists();

        if (!$isClassMember) {
            throw new Exception('Studentam nav atļauts pieteikties šajā praksē - nav attiecīgās klases biedrs.');
        }

        // Pārbauda, vai students jau nav pieteicies šai praksei
        $existingApplication = Application::where('internship_id', $internship->internship_id)
            ->where('student_id', $student->id)
            ->exists();

        if ($existingApplication) {
            throw new Exception('Students jau ir pieteicies šai praksei.');
        }
    }

    /**
     * Validate that the motivation letter is provided.
     *
     * @param string|null $motivationLetter
     * @return void
     * @throws Exception
     */
    private function validateMotivationLetter(?string $motivationLetter): void
    {
        if (empty(trim($motivationLetter ?? ''))) {
            throw new Exception('Motivācijas vēstule ir obligāta.');
        }
    }
}
