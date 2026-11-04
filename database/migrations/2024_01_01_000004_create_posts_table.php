<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('category_id');
            $table->tinyInteger('type')->default(0);
            $table->string('title', 128);
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->timestamp('last_comment_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['is_pinned', 'created_at']);
            $table->index(['like_count', 'comment_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
