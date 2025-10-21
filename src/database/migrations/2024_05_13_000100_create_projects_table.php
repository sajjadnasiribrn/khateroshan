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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default(\App\Enums\ProjectStatusEnum::DRAFT->value)->index();
            $table->string('priority')->default(\App\Enums\ProjectPriorityEnum::NORMAL->value)->index();
            $table->string('type')->default(\App\Enums\ProjectTypeEnum::OTHER->value)->index();
            $table->boolean('recurring')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete()->index();
            $table->decimal('budget', 12, 2)->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
