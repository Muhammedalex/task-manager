<?php

use App\Models\Task;
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
        // Update all existing tasks that don't have a code
        $tasks = Task::whereNull('code')->get();
        
        foreach ($tasks as $task) {
            $task->code = Task::generateUniqueCode();
            $task->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Task::whereNotNull('code')->update(['code' => null]);
    }
};
