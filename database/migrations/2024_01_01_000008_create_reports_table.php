<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id');
            $table->string('reportable_type', 64);
            $table->unsignedBigInteger('reportable_id');
            $table->tinyInteger('reason');
            $table->string('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
