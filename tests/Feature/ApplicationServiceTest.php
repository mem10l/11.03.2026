<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ApplicationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationServiceTest extends TestCase
{
    use DatabaseMigrations;

    private ApplicationService $applicationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required data for each test (after DatabaseMigrations runs migrations)
        $this->seedRequiredData();

        $this->applicationService = new ApplicationService;
    }

    private function seedRequiredData(): void
    {
        // Seed User Roles
        UserRole::create(['role_id' => 1, 'role_name' => 'Admin']);
        UserRole::create(['role_id' => 2, 'role_name' => 'Supervisor']);
        UserRole::create(['role_id' => 3, 'role_name' => 'Student']);

        // Create application statuses
        DB::table('application_statuses')->insert([
            ['status_id' => 1, 'status_name' => 'Pending'],
            ['status_id' => 2, 'status_name' => 'Submitted'],
            ['status_id' => 3, 'status_name' => 'Under Review'],
            ['status_id' => 4, 'status_name' => 'Accepted'],
            ['status_id' => 5, 'status_name' => 'Rejected'],
        ]);

        // Create grading types
        DB::table('grading_types')->insert([
            ['type_id' => 1, 'type_name' => 'Pass/Fail'],
            ['type_id' => 2, 'type_name' => 'Letter Grade'],
            ['type_id' => 3, 'type_name' => 'Numeric'],
            ['type_id' => 4, 'type_name' => '10-point Scale'],
        ]);
    }

    public function test_create_application_successfully(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-21',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'address' => 'Test Address 1',
        ]);

        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);

        $internship = Internship::create([
            'title' => 'Test Internship',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Act
        $application = $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            'Test motivation letter'
        );

        // Assert
        $this->assertNotNull($application);
        $this->assertEquals($internship->internship_id, $application->internship_id);
        $this->assertEquals($student->id, $application->student_id);
        $this->assertEquals($company->company_id, $application->company_id);
        $this->assertEquals('Test motivation letter', $application->motivation_letter);
        $this->assertNotNull($application->submitted_at);
    }

    public function test_create_application_fails_when_user_does_not_exist(): void
    {
        // Arrange
        $class = SchoolClass::create([
            'class_name' => 'DAB-21',
            'school_year' => 2024,
        ]);
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'address' => 'Test Address 1',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $nonExistentUserId = 99999;

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Lietotājs netika atrasts datubāzē.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $nonExistentUserId,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_internship_does_not_exist(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student2@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'name' => 'Test Company 2',
            'email' => 'test2@company.com',
            'address' => 'Test Address 2',
        ]);
        $nonExistentInternshipId = 99999;

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prakse netika atrasta.');

        // Act
        $this->applicationService->createApplication(
            $nonExistentInternshipId,
            $student->id,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_internship_has_invalid_dates(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student3@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-22',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 3',
            'email' => 'test3@company.com',
            'address' => 'Test Address 3',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor2@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 2',
            'start_date' => now()->addMonths(3),
            'end_date' => now()->addMonth(), // End date before start date
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prakses sākuma datums ir vēlāks par beigu datumu.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_internship_has_ended(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student4@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-23',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 4',
            'email' => 'test4@company.com',
            'address' => 'Test Address 4',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor3@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 3',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonth(), // Already ended
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prakse ir beigusies.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_student_not_in_class(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student5@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $differentClass = SchoolClass::create([
            'class_name' => 'DAB-24',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$differentClass->class_id]);

        $company = Company::create([
            'name' => 'Test Company 5',
            'email' => 'test5@company.com',
            'address' => 'Test Address 5',
        ]);
        $otherClass = SchoolClass::create([
            'class_name' => 'DAB-25',
            'school_year' => 2024,
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor4@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 4',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $otherClass->class_id, // Different class
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Studentam nav atļauts pieteikties šajā praksē - nav attiecīgās klases biedrs.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_student_already_applied(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student6@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-26',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 6',
            'email' => 'test6@company.com',
            'address' => 'Test Address 6',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor5@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 5',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Create existing application
        Application::create([
            'internship_id' => $internship->internship_id,
            'student_id' => $student->id,
            'company_id' => $company->company_id,
            'status_id' => 1,
            'motivation_letter' => 'First application',
            'submitted_at' => now(),
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Students jau ir pieteicies šai praksei.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            'Duplicate application'
        );
    }

    public function test_create_application_fails_when_motivation_letter_is_missing(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student7@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-27',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 7',
            'email' => 'test7@company.com',
            'address' => 'Test Address 7',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor6@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 6',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Motivācijas vēstule ir obligāta.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );
    }

    public function test_create_application_fails_when_motivation_letter_is_empty(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student7b@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-27b',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 7b',
            'email' => 'test7b@company.com',
            'address' => 'Test Address 7b',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor6b@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 6b',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Motivācijas vēstule ir obligāta.');

        // Act
        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            ''
        );
    }

    public function test_create_application_is_atomic(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student8@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-28',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 8',
            'email' => 'test8@company.com',
            'address' => 'Test Address 8',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor7@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 7',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $initialApplicationCount = Application::count();

        // Act
        $application = $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            'Test motivation letter'
        );

        // Assert
        $this->assertEquals($initialApplicationCount + 1, Application::count());
        $this->assertEquals('Test motivation letter', $application->motivation_letter);
    }

    public function test_failed_application_logs_to_journal_when_user_not_found(): void
    {
        // Arrange
        $class = SchoolClass::create([
            'class_name' => 'DAB-30',
            'school_year' => 2024,
        ]);
        $company = Company::create([
            'name' => 'Test Company 30',
            'email' => 'test30@company.com',
            'address' => 'Test Address 30',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor30@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 30',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $nonExistentUserId = 99999;
        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Lietotājs netika atrasts datubāzē.');

        $this->applicationService->createApplication(
            $internship->internship_id,
            $nonExistentUserId,
            $company->company_id
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertNull($log->user_id);
        $this->assertEquals(Application::class, $log->entity_type);
        $this->assertNull($log->entity_id);
        $this->assertEquals('Lietotājs nav atrasts datubāzē.', $log->description);
        $this->assertEquals('Lietotājs nav atrasts datubāzē.', $log->metadata['failure_reason']);
        $this->assertEquals($nonExistentUserId, $log->metadata['student_id']);
    }

    public function test_failed_application_logs_to_journal_when_internship_not_found(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student31@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'name' => 'Test Company 31',
            'email' => 'test31@company.com',
            'address' => 'Test Address 31',
        ]);

        $nonExistentInternshipId = 99999;
        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prakse netika atrasta.');

        $this->applicationService->createApplication(
            $nonExistentInternshipId,
            $student->id,
            $company->company_id
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals('Prakse nav atrasta.', $log->description);
        $this->assertEquals('Prakse nav atrasta.', $log->metadata['failure_reason']);
        $this->assertEquals($nonExistentInternshipId, $log->metadata['internship_id']);
    }

    public function test_failed_application_logs_to_journal_when_internship_expired(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student32@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-32',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 32',
            'email' => 'test32@company.com',
            'address' => 'Test Address 32',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor32@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 32',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prakse ir beigusies.');

        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals('Prakse ir beigusies.', $log->description);
        $this->assertEquals('Prakse ir beigusies.', $log->metadata['failure_reason']);
    }

    public function test_failed_application_logs_to_journal_when_student_not_in_class(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student33@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        // Student is NOT added to any class

        $company = Company::create([
            'name' => 'Test Company 33',
            'email' => 'test33@company.com',
            'address' => 'Test Address 33',
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-33',
            'school_year' => 2024,
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor33@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 33',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Studentam nav atļauts pieteikties šajā praksē');

        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals($student->id, $log->user_id);
        $this->assertEquals('Studentam nav atļauts pieteikties šajā praksē.', $log->description);
        $this->assertEquals('Nav attiecīgās klases biedrs.', $log->metadata['failure_reason']);
        $this->assertEquals($student->id, $log->metadata['student_id']);
    }

    public function test_failed_application_logs_to_journal_when_already_applied(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student34@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-34',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 34',
            'email' => 'test34@company.com',
            'address' => 'Test Address 34',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor34@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 34',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        // Create first application
        Application::create([
            'internship_id' => $internship->internship_id,
            'student_id' => $student->id,
            'company_id' => $company->company_id,
            'status_id' => 1,
            'motivation_letter' => 'First motivation letter',
            'submitted_at' => now(),
        ]);

        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Students jau ir pieteicies šai praksei.');

        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            'Second motivation letter'
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals($student->id, $log->user_id);
        $this->assertEquals('Students jau ir pieteicies šai praksei.', $log->description);
        $this->assertEquals('Jau eksistē pieteikums.', $log->metadata['failure_reason']);
    }

    public function test_failed_application_logs_to_journal_when_motivation_letter_missing(): void
    {
        // Arrange
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student35@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);
        $class = SchoolClass::create([
            'class_name' => 'DAB-35',
            'school_year' => 2024,
        ]);
        $student->classes()->sync([$class->class_id]);

        $company = Company::create([
            'name' => 'Test Company 35',
            'email' => 'test35@company.com',
            'address' => 'Test Address 35',
        ]);
        $supervisor = User::create([
            'first_name' => 'John',
            'last_name' => 'Supervisor',
            'email' => 'supervisor35@test.com',
            'role_id' => 2,
            'password' => bcrypt('password'),
        ]);
        $internship = Internship::create([
            'title' => 'Test Internship 35',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $initialLogCount = ActivityLog::count();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Motivācijas vēstule ir obligāta.');

        $this->applicationService->createApplication(
            $internship->internship_id,
            $student->id,
            $company->company_id,
            null
        );

        // Assert - Check that a log entry was created
        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals('Motivācijas vēstule trūkst.', $log->description);
        $this->assertEquals('Motivācijas vēstule ir obligāta.', $log->metadata['failure_reason']);
    }
}
