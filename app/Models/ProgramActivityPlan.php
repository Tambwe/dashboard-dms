<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramActivityPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'program_year',
        'quarter',
        'month',
        'program_indicator_id',
        'program_activity_id',
        'program_sub_activity_id',
        'target_value',
        'achieved_value',
        'achievement_rate',
        'planned_budget',
        'executed_budget',
        'budget_variance',
        'comment',
    ];

    protected $casts = [
        'program_year' => 'integer',
        'month' => 'integer',
        'target_value' => 'decimal:2',
        'achieved_value' => 'decimal:2',
        'achievement_rate' => 'decimal:4',
        'planned_budget' => 'decimal:2',
        'executed_budget' => 'decimal:2',
        'budget_variance' => 'decimal:2',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(ProgramIndicator::class, 'program_indicator_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class, 'program_activity_id');
    }

    public function subActivity(): BelongsTo
    {
        return $this->belongsTo(ProgramSubActivity::class, 'program_sub_activity_id');
    }
}
