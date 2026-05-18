<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sectorObjectives(): HasMany
    {
        return $this->hasMany(ProgramSectorObjective::class);
    }

    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organisation::class,
            'cluster_organisation'
        )->withTimestamps();
    }
}
