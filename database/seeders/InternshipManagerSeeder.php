<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Company;
use App\Models\Grade;
use App\Models\Internship;
use App\Models\Placement;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

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
            \Illuminate\Support\Facades\DB::table('user_roles')->updateOrInsert(
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
            \Illuminate\Support\Facades\DB::table('application_statuses')->updateOrInsert(
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
            \Illuminate\Support\Facades\DB::table('grading_types')->updateOrInsert(
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
            \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
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
            \Illuminate\Support\Facades\DB::table('companies')->updateOrInsert(
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
            \Illuminate\Support\Facades\DB::table('classes')->updateOrInsert(
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

        $this->command->info('Seeding completed successfully!');
    }
}
