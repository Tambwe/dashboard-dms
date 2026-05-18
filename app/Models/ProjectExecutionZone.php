<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExecutionZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'province_id',
        'territoire_id',
        'commune_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function territoire(): BelongsTo
    {
        return $this->belongsTo(Territoire::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }
}
