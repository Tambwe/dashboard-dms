<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramSectorObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'cluster_id',
        'code',
        'label',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function strategicObjectives(): HasMany
    {
        return $this->hasMany(ProgramStrategicObjective::class, 'program_sector_objective_id');
    }
}