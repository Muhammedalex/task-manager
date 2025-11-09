<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('The task that depends on another task');
            $table->foreignId('depends_on_task_id')
                ->constrained('tasks')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('The task that must be completed first');
            $table->timestamps();

            // unique
            $table->unique(['task_id', 'depends_on_task_id'], 'idx_task_dependencies_unique');
            
            // Indexes
            $table->index('task_id', 'idx_task_dependencies_task_id');
            $table->index('depends_on_task_id', 'idx_task_dependencies_depends_on');
        });

        // Note: Self-dependency prevention will be handled at application level
        // MySQL doesn't allow check constraints on columns used in foreign keys
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
