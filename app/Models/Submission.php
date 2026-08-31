<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id', 'code', 'status', 'total_cost', 'remaining_budget', 'execution_log', 'final_state',
    ];

    protected function casts(): array
    {
        return [
            'total_cost' => 'integer',
            'remaining_budget' => 'integer',
            'execution_log' => 'array',
            'final_state' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
