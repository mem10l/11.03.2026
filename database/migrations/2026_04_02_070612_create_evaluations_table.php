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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id('evaluation_id');

            // Foreign keys
            $table->foreignId('application_id')->constrained('applications', 'application_id', 'evaluations_application_id_foreign')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('supervisor_id')->constrained('users', 'id', 'evaluations_supervisor_id_foreign')->onDelete('cascade')->onUpdate('cascade');

            // Evaluation details
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();

            // Timestamp
            $table->timestamp('evaluated_at')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('application_id');
            $table->index('supervisor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
