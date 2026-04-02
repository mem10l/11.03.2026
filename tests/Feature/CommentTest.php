<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentTest extends TestCase
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

    private function createTestData(): array
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

        $application = Application::create([
            'internship_id' => $internship->internship_id,
            'student_id' => $student->id,
            'company_id' => $company->company_id,
            'status_id' => 1,
            'motivation_letter' => 'Test motivation letter',
            'submitted_at' => now(),
        ]);

        $evaluation = Evaluation::create([
            'application_id' => $application->application_id,
            'supervisor_id' => $supervisor->id,
            'grade' => 8.5,
            'feedback' => 'Good work!',
            'evaluated_at' => now(),
        ]);

        return [
            'student' => $student,
            'supervisor' => $supervisor,
            'application' => $application,
            'evaluation' => $evaluation,
        ];
    }

    public function test_create_comment_on_application(): void
    {
        $data = $this->createTestData();

        $payload = [
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Great application!',
            'user_id' => $data['supervisor']->id,
        ];

        $response = $this->postJson('/api/comments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Komentārs izveidots veiksmīgi.')
            ->assertJsonPath('data.content', 'Great application!')
            ->assertJsonPath('data.commentable_type', 'App\Models\Application');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Great application!',
        ]);
    }

    public function test_create_comment_on_evaluation(): void
    {
        $data = $this->createTestData();

        $payload = [
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Well deserved grade!',
            'user_id' => $data['student']->id,
        ];

        $response = $this->postJson('/api/comments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Komentārs izveidots veiksmīgi.')
            ->assertJsonPath('data.content', 'Well deserved grade!')
            ->assertJsonPath('data.commentable_type', 'App\Models\Evaluation');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Well deserved grade!',
        ]);
    }

    public function test_get_comments_for_application(): void
    {
        $data = $this->createTestData();

        Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'First comment',
            'user_id' => $data['supervisor']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Second comment',
            'user_id' => $data['student']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Evaluation comment',
            'user_id' => $data['supervisor']->id,
        ]);

        $response = $this->getJson('/api/comments?commentable_type=App\Models\Application&commentable_id='.$data['application']->application_id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_comments_for_evaluation(): void
    {
        $data = $this->createTestData();

        Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Application comment',
            'user_id' => $data['supervisor']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'First evaluation comment',
            'user_id' => $data['student']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Second evaluation comment',
            'user_id' => $data['supervisor']->id,
        ]);

        $response = $this->getJson('/api/comments?commentable_type=App\Models\Evaluation&commentable_id='.$data['evaluation']->evaluation_id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_update_comment(): void
    {
        $data = $this->createTestData();

        $comment = Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Original content',
            'user_id' => $data['supervisor']->id,
        ]);

        $payload = [
            'content' => 'Updated content',
        ];

        $response = $this->putJson('/api/comments/'.$comment->comment_id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Komentārs atjaunināts veiksmīgi.')
            ->assertJsonPath('data.content', 'Updated content');

        $this->assertDatabaseHas('comments', [
            'comment_id' => $comment->comment_id,
            'content' => 'Updated content',
        ]);
    }

    public function test_delete_comment(): void
    {
        $data = $this->createTestData();

        $comment = Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'To be deleted',
            'user_id' => $data['supervisor']->id,
        ]);

        $response = $this->deleteJson('/api/comments/'.$comment->comment_id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Komentārs dzēsts veiksmīgi.');

        $this->assertDatabaseMissing('comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    public function test_show_single_comment(): void
    {
        $data = $this->createTestData();

        $comment = Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Single comment',
            'user_id' => $data['supervisor']->id,
        ]);

        $response = $this->getJson('/api/comments/'.$comment->comment_id);

        $response->assertStatus(200)
            ->assertJsonPath('data.comment_id', $comment->comment_id)
            ->assertJsonPath('data.content', 'Single comment');
    }

    public function test_polymorphic_relationship_returns_correct_parent(): void
    {
        $data = $this->createTestData();

        $applicationComment = Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Application comment',
            'user_id' => $data['supervisor']->id,
        ]);

        $evaluationComment = Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Evaluation comment',
            'user_id' => $data['student']->id,
        ]);

        // Test Application comment returns Application
        $this->assertInstanceOf(Application::class, $applicationComment->commentable);
        $this->assertEquals($data['application']->application_id, $applicationComment->commentable->application_id);

        // Test Evaluation comment returns Evaluation
        $this->assertInstanceOf(Evaluation::class, $evaluationComment->commentable);
        $this->assertEquals($data['evaluation']->evaluation_id, $evaluationComment->commentable->evaluation_id);
    }

    public function test_application_has_many_comments(): void
    {
        $data = $this->createTestData();

        Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Comment 1',
            'user_id' => $data['supervisor']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'content' => 'Comment 2',
            'user_id' => $data['student']->id,
        ]);

        $application = Application::with('comments')->find($data['application']->application_id);

        $this->assertCount(2, $application->comments);
    }

    public function test_evaluation_has_many_comments(): void
    {
        $data = $this->createTestData();

        Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Comment 1',
            'user_id' => $data['student']->id,
        ]);

        Comment::create([
            'commentable_type' => 'App\Models\Evaluation',
            'commentable_id' => $data['evaluation']->evaluation_id,
            'content' => 'Comment 2',
            'user_id' => $data['supervisor']->id,
        ]);

        $evaluation = Evaluation::with('comments')->find($data['evaluation']->evaluation_id);

        $this->assertCount(2, $evaluation->comments);
    }

    public function test_cannot_create_comment_with_invalid_type(): void
    {
        $data = $this->createTestData();

        $payload = [
            'commentable_type' => 'App\Models\InvalidModel',
            'commentable_id' => 1,
            'content' => 'Invalid comment',
            'user_id' => $data['supervisor']->id,
        ];

        $response = $this->postJson('/api/comments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('commentable_type');
    }

    public function test_cannot_create_comment_without_content(): void
    {
        $data = $this->createTestData();

        $payload = [
            'commentable_type' => 'App\Models\Application',
            'commentable_id' => $data['application']->application_id,
            'user_id' => $data['supervisor']->id,
        ];

        $response = $this->postJson('/api/comments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }
}
