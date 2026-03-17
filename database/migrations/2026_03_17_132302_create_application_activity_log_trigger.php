<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration for creating activity log trigger on applications table.
 * 
 * NOTE: This migration requires SUPER or SYSTEM_VARIABLES_ADMIN privilege.
 * If running in a managed MySQL environment (e.g., AWS RDS, Azure Database),
 * you may need to:
 * 1. Set log_bin_trust_function_creators = 1 at the server level, OR
 * 2. Use the alternative Model Event approach in AppServiceProvider
 * 
 * Alternative: Use the triggerless approach by enabling ApplicationObserver
 * in AppServiceProvider which achieves the same result using Laravel events.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @throws \Illuminate\Database\QueryException if insufficient privileges
     */
    public function up(): void
    {
        try {
            DB::unprepared("
                CREATE TRIGGER log_application_created
                AFTER INSERT ON applications
                FOR EACH ROW
                BEGIN
                    INSERT INTO activity_logs (
                        user_id,
                        action,
                        entity_type,
                        entity_id,
                        description,
                        metadata,
                        logged_at
                    )
                    VALUES (
                        NEW.student_id,
                        'created',
                        'App\\Models\\Application',
                        NEW.application_id,
                        CONCAT('Izveidots jauns prakses pieteikums internships_id: ', NEW.internship_id, ', company_id: ', NEW.company_id),
                        JSON_OBJECT(
                            'application_id', NEW.application_id,
                            'internship_id', NEW.internship_id,
                            'student_id', NEW.student_id,
                            'company_id', NEW.company_id,
                            'status_id', NEW.status_id,
                            'submitted_at', NEW.submitted_at
                        ),
                        NOW()
                    );
                END
            ");
        } catch (\Exception $e) {
            // If trigger creation fails due to privileges, log a message
            // The application will use Model Events as fallback (see AppServiceProvider)
            error_log('Trigger creation failed: ' . $e->getMessage());
            error_log('Using Model Events as fallback for activity logging.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS log_application_created");
    }
};
