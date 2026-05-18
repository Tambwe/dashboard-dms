<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'organisation_id',
        'cluster_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'end_date',
        'funding_amount',
        'donors_json',
        'beneficiaries_female_0_17',
        'beneficiaries_female_18_59',
        'beneficiaries_female_60_plus',
        'beneficiaries_male_0_17',
        'beneficiaries_male_18_59',
        'beneficiaries_male_60_plus',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'funding_amount' => 'decimal:2',
        'donors_json'  => 'array',
    ];

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class);
    }

    public function executionZones(): HasMany
    {
        return $this->hasMany(ProjectExecutionZone::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }

    /** Retourne la liste des bailleurs (tableau de noms) */
    public function getDonorsListAttribute(): array
    {
        return $this->donors_json ?? [];
    }

    /** Total bénéficiaires femmes */
    public function getTotalFemalesAttribute(): int
    {
        return (int)$this->beneficiaries_female_0_17
             + (int)$this->beneficiaries_female_18_59
             + (int)$this->beneficiaries_female_60_plus;
    }

    /** Total bénéficiaires hommes */
    public function getTotalMalesAttribute(): int
    {
        return (int)$this->beneficiaries_male_0_17
             + (int)$this->beneficiaries_male_18_59
             + (int)$this->beneficiaries_male_60_plus;
    }

    /** Total bénéficiaires global */
    public function getTotalBeneficiariesAttribute(): int
    {
        return $this->total_females + $this->total_males;
    }
}
