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
        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id');

            // Polymorphic relationship columns
            $table->morphs('commentable'); // commentable_id un commentable_type

            // Comment content
            $table->text('content');

            // Author of the comment
            $table->foreignId('user_id')->constrained('users', 'id', 'comments_user_id_foreign')->onDelete('cascade')->onUpdate('cascade');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('commentable_type');
            $table->index('commentable_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
