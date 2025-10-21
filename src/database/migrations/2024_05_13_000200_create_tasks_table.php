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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->longText('description');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->index();
            $table->string('status')->default(\App\Enums\TaskStatusEnum::TODO->value)->index();
            $table->date('due_date')->nullable()->index();
            $table->json('tags')->nullable();
            $table->unsignedInteger('estimated_time')->nullable();
            $table->unsignedInteger('actual_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
