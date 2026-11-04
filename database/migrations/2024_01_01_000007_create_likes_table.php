<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('likeable_type', 64);
            $table->unsignedBigInteger('likeable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'likeable_type', 'likeable_id'], 'ux_likes_user_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
