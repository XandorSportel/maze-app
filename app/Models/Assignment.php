<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'map_definition', 'costs', 'start_capital', 'is_custom', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'costs' => 'array',
            'start_capital' => 'integer',
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function tiles(): array
    {
        return preg_split('/\s+/', trim($this->map_definition)) ?: [];
    }
}
