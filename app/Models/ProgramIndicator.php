<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'code',
        'label',
        'program_strategic_objective_id',
        'unit',
        'frequency',
        'owner',
        'verification_source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(ProgramActivity::class);
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(ProgramStrategicObjective::class, 'program_strategic_objective_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ProgramActivityPlan::class);
    }
}
