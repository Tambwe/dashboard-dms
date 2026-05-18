<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramSubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'code',
        'label',
        'program_activity_id',
        'site_name',
        'province',
        'territoire',
        'health_zone',
        'planned_start_date',
        'planned_end_date',
        'status',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class, 'program_activity_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ProgramActivityPlan::class);
    }
}
