<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'activity_name',
        'program_indicator_id',
        'program_activity_id',
        'program_sub_activity_id',
        'activity_cost',
        'site_id',
        'province_id',
        'territoire_id',
        'commune_id',
        'statut_beneficiaire',
        'beneficiaries_by_status',
        'girls_0_17',
        'girls_18_59',
        'girls_60_plus',
        'boys_0_17',
        'boys_18_59',
        'boys_60_plus',
        'persons_with_disabilities',
        'comment',
        'reporting_date',
    ];

    protected $casts = [
        'activity_cost' => 'decimal:2',
        'beneficiaries_by_status' => 'array',
        'reporting_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
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

    public function programIndicator(): BelongsTo
    {
        return $this->belongsTo(ProgramIndicator::class, 'program_indicator_id');
    }

    public function programActivity(): BelongsTo
    {
        return $this->belongsTo(ProgramActivity::class, 'program_activity_id');
    }

    public function programSubActivity(): BelongsTo
    {
        return $this->belongsTo(ProgramSubActivity::class, 'program_sub_activity_id');
    }
}
