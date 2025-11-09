<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'description',
        'status',
        'due_date',
        'assigned_to',
        'created_by',
        'completed_at',
        'canceled_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Get the user assigned to this task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tasks that this task depends on.
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    /**
     * Get the tasks that depend on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    /**
     * Check if all dependencies are completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->dependencies()
            ->where('status', '!=', 'completed')
            ->doesntExist();
    }

    /**
     * Get dependencies statistics.
     *
     * @return array
     */
    public function getDependenciesStats(): array
    {
        $dependencies = $this->dependencies;
        $total = $dependencies->count();
        $completed = $dependencies->where('status', 'completed')->count();
        $pending = $dependencies->where('status', 'pending')->count();
        $inProgress = $dependencies->where('status', 'in_progress')->count();
        $canceled = $dependencies->where('status', 'canceled')->count();

        // Calculate completion percentage
        $completionPercentage = $total > 0 
            ? round(($completed / $total) * 100, 2) 
            : 100;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'canceled' => $canceled,
            'remaining' => $total - $completed,
            'can_be_completed' => $this->canBeCompleted(),
            'completion_percentage' => $completionPercentage,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->code)) {
                $task->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique task code.
     *
     * @return string
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = 'TSK-' . strtoupper(Str::random(12));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
