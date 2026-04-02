<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\Placement;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InternshipManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed User Roles
        $this->command->info('Seeding User Roles...');
        $userRoles = [
            ['role_id' => 1, 'role_name' => 'Admin'],
            ['role_id' => 2, 'role_name' => 'Supervisor'],
            ['role_id' => 3, 'role_name' => 'Student'],
            ['role_id' => 4, 'role_name' => 'Company Representative'],
        ];
        foreach ($userRoles as $role) {
            DB::table('user_roles')->updateOrInsert(
                ['role_id' => $role['role_id']],
                ['role_name' => $role['role_name']]
            );
        }

        // Seed Application Statuses
        $this->command->info('Seeding Application Statuses...');
        $applicationStatuses = [
            ['status_id' => 1, 'status_name' => 'Pending'],
            ['status_id' => 2, 'status_name' => 'Submitted'],
            ['status_id' => 3, 'status_name' => 'Under Review'],
            ['status_id' => 4, 'status_name' => 'Accepted'],
            ['status_id' => 5, 'status_name' => 'Rejected'],
            ['status_id' => 6, 'status_name' => 'Withdrawn'],
        ];
        foreach ($applicationStatuses as $status) {
            DB::table('application_statuses')->updateOrInsert(
                ['status_id' => $status['status_id']],
                ['status_name' => $status['status_name']]
            );
        }

        // Seed Grading Types
        $this->command->info('Seeding Grading Types...');
        $gradingTypes = [
            ['type_id' => 1, 'type_name' => 'Pass/Fail'],
            ['type_id' => 2, 'type_name' => 'Letter Grade'],
            ['type_id' => 3, 'type_name' => 'Numeric'],
            ['type_id' => 4, 'type_name' => '10-point Scale'],
        ];
        foreach ($gradingTypes as $type) {
            DB::table('grading_types')->updateOrInsert(
                ['type_id' => $type['type_id']],
                ['type_name' => $type['type_name']]
            );
        }

        // Seed Sample Users
        $this->command->info('Seeding Users...');
        $users = [
            [
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@internshipManager.lv',
                'role_id' => 1,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'id' => 2,
                'first_name' => 'John',
                'last_name' => 'Supervisor',
                'email' => 'supervisor@internshipManager.lv',
                'role_id' => 2,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            [
                'id' => 3,
                'first_name' => 'Jane',
                'last_name' => 'Student',
                'email' => 'student@internshipManager.lv',
                'role_id' => 3,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        ];
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['id' => $user['id']],
                $user
            );
        }

        // Create additional students using factory
        User::factory()->count(10)->student()->create();

        // Create additional supervisors using factory
        User::factory()->count(3)->supervisor()->create();

        // Seed Sample Companies
        $this->command->info('Seeding Companies...');
        $companies = [
            [
                'company_id' => 1,
                'name' => 'Tech Solutions SIA',
                'email' => 'info@techsolutions.lv',
                'address' => 'Brivibas iela 1, Riga',
            ],
            [
                'company_id' => 2,
                'name' => 'Digital Innovations AS',
                'email' => 'contact@digitalinnovations.lv',
                'address' => 'Elizabetes iela 45, Riga',
            ],
            [
                'company_id' => 3,
                'name' => 'Baltic Software SIA',
                'email' => 'hello@balticsoftware.lv',
                'address' => 'Krasta iela 76, Riga',
            ],
        ];
        foreach ($companies as $company) {
            DB::table('companies')->updateOrInsert(
                ['company_id' => $company['company_id']],
                $company
            );
        }

        // Create additional companies using factory
        Company::factory()->count(5)->create();

        // Seed Sample Classes
        $this->command->info('Seeding Classes...');
        $classes = [
            [
                'class_id' => 1,
                'class_name' => 'DAB-21',
                'school_year' => 2024,
            ],
            [
                'class_id' => 2,
                'class_name' => 'DAB-22',
                'school_year' => 2024,
            ],
            [
                'class_id' => 3,
                'class_name' => 'DAB-23',
                'school_year' => 2024,
            ],
        ];
        foreach ($classes as $class) {
            DB::table('classes')->updateOrInsert(
                ['class_id' => $class['class_id']],
                $class
            );
        }

        // Create additional classes using factory
        SchoolClass::factory()->count(3)->create();

        // Assign students to classes
        $this->command->info('Assigning students to classes...');
        $students = User::where('role_id', 3)->get();
        $classes = SchoolClass::all();

        foreach ($students as $student) {
            $randomClass = $classes->random();
            $student->classes()->syncWithoutDetaching([$randomClass->class_id]);
        }

        // Create Internships
        $this->command->info('Seeding Internships...');
        Internship::factory()->count(5)->create();

        // Create Applications
        $this->command->info('Seeding Applications...');
        Application::factory()->count(15)->create();

        // Create Placements
        $this->command->info('Seeding Placements...');
        Placement::factory()->count(10)->create();

        // Create Grades
        $this->command->info('Seeding Grades...');
        Grade::factory()->count(8)->create();

        // Create Evaluations
        $this->command->info('Seeding Evaluations...');
        $this->createSampleEvaluations();

        // Create Comments (polymorphic - for Applications and Evaluations)
        $this->command->info('Seeding Comments...');
        $this->createSampleComments();

        $this->command->info('Seeding completed successfully!');
    }

    /**
     * Create sample evaluations.
     */
    private function createSampleEvaluations(): void
    {
        $applications = Application::with(['student', 'internship'])->get();
        $supervisors = User::where('role_id', 2)->get();

        if ($applications->isEmpty() || $supervisors->isEmpty()) {
            return;
        }

        // Create evaluations for some applications
        $applications->take(5)->each(function ($application) use ($supervisors) {
            Evaluation::create([
                'application_id' => $application->application_id,
                'supervisor_id' => $supervisors->random()->id,
                'grade' => rand(60, 100) / 10,
                'feedback' => 'Labs darbs! Turpiniet tikpat labi.',
                'evaluated_at' => now(),
            ]);
        });
    }

    /**
     * Create sample comments for applications and evaluations.
     */
    private function createSampleComments(): void
    {
        $applications = Application::all();
        $evaluations = Evaluation::all();
        $users = User::all();

        // Create comments for applications
        $applications->each(function ($application) use ($users) {
            $commentCount = rand(1, 3);
            for ($i = 0; $i < $commentCount; $i++) {
                Comment::create([
                    'commentable_type' => 'App\Models\Application',
                    'commentable_id' => $application->application_id,
                    'content' => $this->getApplicationCommentText($i),
                    'user_id' => $users->random()->id,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        });

        // Create comments for evaluations
        $evaluations->each(function ($evaluation) use ($users) {
            $commentCount = rand(1, 2);
            for ($i = 0; $i < $commentCount; $i++) {
                Comment::create([
                    'commentable_type' => 'App\Models\Evaluation',
                    'commentable_id' => $evaluation->evaluation_id,
                    'content' => $this->getEvaluationCommentText($i),
                    'user_id' => $users->random()->id,
                    'created_at' => now()->subDays(rand(1, 20)),
                    'updated_at' => now()->subDays(rand(1, 20)),
                ]);
            }
        });
    }

    /**
     * Get sample application comment text.
     */
    private function getApplicationCommentText(int $index): string
    {
        $comments = [
            'Labs pieteikums! Vēlu veiksmi.',
            'Pieteikums apstiprināts. Lūdzu, sazinieties ar uzņēmumu.',
            'Nepieciešami papildu dokumenti.',
            'Paldies par pieteikumu. Mēs to izskatīsim tuvākajā laikā.',
            'Izcils motivācijas vēstule!',
            'Lūdzu, precizējiet prakses laiku.',
        ];

        return $comments[$index % count($comments)];
    }

    /**
     * Get sample evaluation comment text.
     */
    private function getEvaluationCommentText(int $index): string
    {
        $comments = [
            'Pelnīta atzīme! Ļoti labs darbs.',
            'Students parādīja izcilas prasmes.',
            'Ieteicams turpināt pilnveidot zināšanas.',
            'Labs sniegums prakses laikā.',
            'Profesionāla pieeja darbam.',
        ];

        return $comments[$index % count($comments)];
    }
}
