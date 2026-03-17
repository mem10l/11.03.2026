<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // User Roles
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name', 45);
        });

        // Add columns to existing users table
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name', 45)->after('first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('email')->constrained('user_roles', 'role_id', 'users_role_id_foreign')->onUpdate('cascade');
        });

        // Application Statuses
        Schema::create('application_statuses', function (Blueprint $table) {
            $table->id('status_id');
            $table->string('status_name', 45);
        });

        // Companies
        Schema::create('companies', function (Blueprint $table) {
            $table->id('company_id');
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
        });

        // Grading Types
        Schema::create('grading_types', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_name', 45);
        });

        // Classes
        Schema::create('classes', function (Blueprint $table) {
            $table->id('class_id');
            $table->string('class_name', 45);
            $table->year('school_year');
        });

        // Class Members
        Schema::create('class_members', function (Blueprint $table) {
            $table->id('member_id');
            $table->foreignId('class_id')->constrained('classes', 'class_id', 'class_members_class_id_foreign')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('user_id')->constrained('users', 'id', 'class_members_user_id_foreign')->onDelete('cascade')->onUpdate('cascade');
            $table->unique(['class_id', 'user_id']);
        });

        // Internships
        Schema::create('internships', function (Blueprint $table) {
            $table->id('internship_id');
            $table->string('title', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('class_id')->constrained('classes', 'class_id', 'internships_class_id_foreign')->onUpdate('cascade');
            $table->foreignId('supervisor_id')->constrained('users', 'id', 'internships_supervisor_id_foreign')->onUpdate('cascade');
            $table->foreignId('grading_type_id')->constrained('grading_types', 'type_id', 'internships_grading_type_id_foreign')->onUpdate('cascade');
        });

        // Applications
        Schema::create('applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('internship_id')->constrained('internships', 'internship_id', 'applications_internship_id_foreign')->onUpdate('cascade');
            $table->foreignId('student_id')->constrained('users', 'id', 'applications_student_id_foreign')->onUpdate('cascade');
            $table->foreignId('company_id')->constrained('companies', 'company_id', 'applications_company_id_foreign')->onUpdate('cascade');
            $table->foreignId('status_id')->constrained('application_statuses', 'status_id', 'applications_status_id_foreign')->onUpdate('cascade');
            $table->longText('motivation_letter')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
        });

        // Placements
        Schema::create('placements', function (Blueprint $table) {
            $table->id('placement_id');
            $table->foreignId('internship_id')->constrained('internships', 'internship_id', 'placements_internship_id_foreign')->onUpdate('cascade');
            $table->foreignId('student_id')->constrained('users', 'id', 'placements_student_id_foreign')->onUpdate('cascade');
            $table->foreignId('company_id')->constrained('companies', 'company_id', 'placements_company_id_foreign')->onUpdate('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
        });

        // Grades
        Schema::create('grades', function (Blueprint $table) {
            $table->id('grade_id');
            $table->foreignId('internship_id')->constrained('internships', 'internship_id', 'grades_internship_id_foreign')->onUpdate('cascade');
            $table->foreignId('student_id')->constrained('users', 'id', 'grades_student_id_foreign')->onUpdate('cascade');
            $table->string('grade', 20);
            $table->longText('comment')->nullable();
            $table->unique(['internship_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('placements');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('internships');
        Schema::dropIfExists('class_members');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('grading_types');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('application_statuses');

        // Remove foreign key and columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
        });

        Schema::dropIfExists('user_roles');
    }
};
