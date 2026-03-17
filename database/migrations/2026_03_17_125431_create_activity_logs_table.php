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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            
            // User who performed the action
            $table->foreignId('user_id')->nullable()->constrained('users', 'id', 'activity_logs_user_id_foreign')->onDelete('set null')->onUpdate('cascade');
            
            // Action type (e.g., 'created', 'updated', 'deleted', 'viewed', 'login', 'logout')
            $table->string('action', 50);
            
            // Entity type affected (e.g., 'App\\Models\\Internship', 'App\\Models\\Application')
            $table->string('entity_type', 100)->nullable();
            
            // Entity ID that was affected
            $table->unsignedBigInteger('entity_id')->nullable();
            
            // Description of the action
            $table->text('description')->nullable();
            
            // IP address from where the action was performed
            $table->ipAddress('ip_address')->nullable();
            
            // User agent string
            $table->text('user_agent')->nullable();
            
            // Additional metadata (JSON format)
            $table->json('metadata')->nullable();
            
            // Timestamp of the action
            $table->timestamp('logged_at')->useCurrent();
            
            // Indexes for faster queries
            $table->index('user_id');
            $table->index('action');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
