<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRequiredData();
    }

    private function seedRequiredData(): void
    {
        UserRole::create(['role_id' => 1, 'role_name' => 'Admin']);
        UserRole::create(['role_id' => 2, 'role_name' => 'Supervisor']);
        UserRole::create(['role_id' => 3, 'role_name' => 'Student']);

        DB::table('application_statuses')->insert([
            ['status_id' => 1, 'status_name' => 'Pending'],
            ['status_id' => 2, 'status_name' => 'Submitted'],
            ['status_id' => 3, 'status_name' => 'Under Review'],
            ['status_id' => 4, 'status_name' => 'Accepted'],
            ['status_id' => 5, 'status_name' => 'Rejected'],
        ]);

        DB::table('grading_types')->insert([
            ['type_id' => 1, 'type_name' => 'Pass/Fail'],
            ['type_id' => 2, 'type_name' => 'Letter Grade'],
            ['type_id' => 3, 'type_name' => 'Numeric'],
            ['type_id' => 4, 'type_name' => '10-point Scale'],
        ]);
    }

    private function createValidApplicationData(): array
    {
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

        return [
            'student' => $student,
            'class' => $class,
            'company' => $company,
            'supervisor' => $supervisor,
            'internship' => $internship,
        ];
    }

    public function test_create_application_successfully_via_api(): void
    {
        $data = $this->createValidApplicationData();

        $payload = [
            'internship_id' => $data['internship']->internship_id,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => 'Test motivation letter',
        ];

        $response = $this->postJson('/api/applications', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Prakses pieteikums izveidots veiksmīgi.');

        $this->assertDatabaseHas('applications', [
            'student_id' => $data['student']->id,
            'internship_id' => $data['internship']->internship_id,
        ]);
    }

    public function test_api_logs_failed_application_when_user_not_found(): void
    {
        // Note: This scenario is caught by validation at API level (exists rule)
        // The service layer logging is tested in ApplicationServiceTest
        $data = $this->createValidApplicationData();

        $payload = [
            'internship_id' => $data['internship']->internship_id,
            'student_id' => 99999,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => 'Test motivation letter',
        ];

        $response = $this->postJson('/api/applications', $payload);

        // Validation catches this before service layer
        $response->assertStatus(422)
            ->assertJsonFragment(['errors' => ['student_id' => ['The selected student id is invalid.']]]);
    }

    public function test_api_logs_failed_application_when_internship_not_found(): void
    {
        // Note: This scenario is caught by validation at API level (exists rule)
        // The service layer logging is tested in ApplicationServiceTest
        $data = $this->createValidApplicationData();

        $payload = [
            'internship_id' => 99999,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => 'Test motivation letter',
        ];

        $response = $this->postJson('/api/applications', $payload);

        // Validation catches this before service layer
        $response->assertStatus(422)
            ->assertJsonFragment(['errors' => ['internship_id' => ['The selected internship id is invalid.']]]);
    }

    public function test_api_logs_failed_application_when_internship_expired(): void
    {
        $data = $this->createValidApplicationData();

        // Update internship to be expired - this passes validation but fails in service
        $data['internship']->update([
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subMonths(3),
        ]);

        $payload = [
            'internship_id' => $data['internship']->internship_id,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => 'Test motivation letter',
        ];

        $initialLogCount = ActivityLog::count();

        $response = $this->postJson('/api/applications', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kļūda izveidojot pieteikumu.');

        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals('Prakse ir beigusies.', $log->description);
        $this->assertEquals('Prakse ir beigusies.', $log->metadata['failure_reason']);
    }

    public function test_api_logs_failed_application_when_student_not_in_class(): void
    {
        $student = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student2@test.com',
            'role_id' => 3,
            'password' => bcrypt('password'),
        ]);

        $class = SchoolClass::create([
            'class_name' => 'DAB-22',
            'school_year' => 2024,
        ]);

        $company = Company::create([
            'name' => 'Test Company 2',
            'email' => 'test2@company.com',
            'address' => 'Test Address 2',
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
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
            'class_id' => $class->class_id,
            'supervisor_id' => $supervisor->id,
            'grading_type_id' => 1,
        ]);

        $payload = [
            'internship_id' => $internship->internship_id,
            'student_id' => $student->id,
            'company_id' => $company->company_id,
            'motivation_letter' => 'Test motivation letter',
        ];

        $initialLogCount = ActivityLog::count();

        $response = $this->postJson('/api/applications', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kļūda izveidojot pieteikumu.');

        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals($student->id, $log->user_id);
        $this->assertEquals('Studentam nav atļauts pieteikties šajā praksē.', $log->description);
        $this->assertEquals('Nav attiecīgās klases biedrs.', $log->metadata['failure_reason']);
    }

    public function test_api_logs_failed_application_when_already_applied(): void
    {
        $data = $this->createValidApplicationData();

        Application::create([
            'internship_id' => $data['internship']->internship_id,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'status_id' => 1,
            'motivation_letter' => 'First motivation letter',
            'submitted_at' => now(),
        ]);

        $initialLogCount = ActivityLog::count();

        $payload = [
            'internship_id' => $data['internship']->internship_id,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => 'Second motivation letter',
        ];

        $response = $this->postJson('/api/applications', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kļūda izveidojot pieteikumu.');

        // The trigger creates a log for the first application, so we expect 2 new logs
        // But we're looking for the 'application_failed' log specifically
        $this->assertGreaterThan($initialLogCount, ActivityLog::count());

        $log = ActivityLog::where('action', 'application_failed')
            ->latest('logged_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals($data['student']->id, $log->user_id);
        $this->assertEquals('Students jau ir pieteicies šai praksei.', $log->description);
        $this->assertEquals('Jau eksistē pieteikums.', $log->metadata['failure_reason']);
    }

    public function test_api_logs_failed_application_when_motivation_letter_missing(): void
    {
        $data = $this->createValidApplicationData();

        // Use empty string - passes 'nullable' validation but fails in service
        $payload = [
            'internship_id' => $data['internship']->internship_id,
            'student_id' => $data['student']->id,
            'company_id' => $data['company']->company_id,
            'motivation_letter' => '',
        ];

        $initialLogCount = ActivityLog::count();

        $response = $this->postJson('/api/applications', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kļūda izveidojot pieteikumu.');

        $this->assertEquals($initialLogCount + 1, ActivityLog::count());

        $log = ActivityLog::latest('logged_at')->first();
        $this->assertEquals('application_failed', $log->action);
        $this->assertEquals('Motivācijas vēstule trūkst.', $log->description);
        $this->assertEquals('Motivācijas vēstule ir obligāta.', $log->metadata['failure_reason']);
    }
}
