<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('action', 64);
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('detail')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};
