<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip stored procedure creation on SQLite (used for testing)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP PROCEDURE IF EXISTS create_application');
        DB::statement("
            CREATE PROCEDURE create_application(
                IN p_internship_id INT,
                IN p_student_id INT,
                IN p_company_id INT,
                IN p_motivation_letter TEXT,
                OUT p_application_id INT,
                OUT p_error_message VARCHAR(255)
            )
            BEGIN
                DECLARE v_internship_id INT;
                DECLARE v_internship_end_date DATE;
                DECLARE v_class_id INT;
                DECLARE v_is_class_member INT DEFAULT 0;
                DECLARE v_existing_application INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SET p_application_id = NULL;
                    SET p_error_message = 'Datubāzes kļūda izveidojot pieteikumu.';
                END;

                START TRANSACTION;

                -- a) Pārbauda, vai lietotājs ir datubāzē
                IF NOT EXISTS (SELECT 1 FROM users WHERE id = p_student_id) THEN
                    SET p_application_id = NULL;
                    SET p_error_message = 'Lietotājs nav atrasts datubāzē.';
                    ROLLBACK;
                ELSE
                    -- b) Pārbauda, vai prakse ir derīga
                    SELECT internship_id, end_date, class_id
                    INTO v_internship_id, v_internship_end_date, v_class_id
                    FROM internships
                    WHERE internship_id = p_internship_id;

                    IF v_internship_id IS NULL THEN
                        SET p_application_id = NULL;
                        SET p_error_message = 'Prakse nav atrasta.';
                        ROLLBACK;
                    ELSEIF v_internship_end_date < CURDATE() THEN
                        SET p_application_id = NULL;
                        SET p_error_message = 'Prakse ir beigusies.';
                        ROLLBACK;
                    ELSE
                        -- c) Pārbauda, vai lietotājam ir atļauts pieteikties šajā praksē
                        SELECT COUNT(*) INTO v_is_class_member
                        FROM class_members
                        WHERE class_id = v_class_id AND user_id = p_student_id;

                        IF v_is_class_member = 0 THEN
                            SET p_application_id = NULL;
                            SET p_error_message = 'Studentam nav atļauts pieteikties šajā praksē - nav attiecīgās klases biedrs.';
                            ROLLBACK;
                        ELSE
                            -- Pārbauda, vai students jau nav pieteicies šai praksei
                            SELECT COUNT(*) INTO v_existing_application
                            FROM applications
                            WHERE internship_id = p_internship_id AND student_id = p_student_id;

                            IF v_existing_application > 0 THEN
                                SET p_application_id = NULL;
                                SET p_error_message = 'Students jau ir pieteicies šai praksei.';
                                ROLLBACK;
                            ELSE
                                -- Izveido prakses pieteikumu
                                INSERT INTO applications (
                                    internship_id,
                                    student_id,
                                    company_id,
                                    status_id,
                                    motivation_letter,
                                    submitted_at
                                ) VALUES (
                                    p_internship_id,
                                    p_student_id,
                                    p_company_id,
                                    1,
                                    p_motivation_letter,
                                    NOW()
                                );

                                SET p_application_id = LAST_INSERT_ID();
                                SET p_error_message = NULL;

                                COMMIT;
                            END IF;
                        END IF;
                    END IF;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip stored procedure drop on SQLite (used for testing)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP PROCEDURE IF EXISTS create_application');
    }
};
