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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('Task title');
            $table->text('description')->nullable()->comment('Task description');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'canceled'])
                ->default('pending')
                ->comment('Task status: pending, in_progress, completed, canceled');
            $table->date('due_date')->nullable()->comment('Task due date');
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('User assigned to this task');
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('User who created this task');
            $table->timestamp('completed_at')->nullable()->comment('Timestamp when task was completed');
            $table->timestamp('canceled_at')->nullable()->comment('Timestamp when task was canceled');
            $table->softDeletes()->comment('Soft delete timestamp');
            $table->timestamps();

            // Indexes 
            $table->index('status', 'idx_tasks_status');
            $table->index('due_date', 'idx_tasks_due_date');
            $table->index('assigned_to', 'idx_tasks_assigned_to');
            $table->index('created_by', 'idx_tasks_created_by');
            $table->index(['status', 'due_date'], 'idx_tasks_status_due_date');
            $table->index(['assigned_to', 'status'], 'idx_tasks_assigned_status');
            $table->index('created_at', 'idx_tasks_created_at');
        });

        // Fulltext index only for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tasks ADD FULLTEXT INDEX idx_tasks_fulltext (title, description)');
        }

        // Check constraint only for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tasks ADD CONSTRAINT chk_tasks_status CHECK (status IN (\'pending\', \'in_progress\', \'completed\', \'canceled\'))');
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
