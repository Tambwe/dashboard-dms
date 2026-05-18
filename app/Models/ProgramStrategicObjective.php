<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStrategicObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'program_sector_objective_id',
    ];

    public function sectorObjective(): BelongsTo
    {
        return $this->belongsTo(ProgramSectorObjective::class, 'program_sector_objective_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(ProgramIndicator::class, 'program_strategic_objective_id');
    }
}