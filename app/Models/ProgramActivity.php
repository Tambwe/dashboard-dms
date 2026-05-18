<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'code',
        'label',
        'program_indicator_id',
        'program_axis',
        'project_lead',
        'status',
        'planned_start_date',
        'planned_end_date',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(ProgramIndicator::class, 'program_indicator_id');
    }

    public function subActivities(): HasMany
    {
        return $this->hasMany(ProgramSubActivity::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ProgramActivityPlan::class);
    }
}
