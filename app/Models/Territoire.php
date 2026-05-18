<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Territoire extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pcode',
        'province_id',
        'area_sqkm',
        'center_lat',
        'center_lon',
        'geometry',
        'properties',
    ];

    protected $casts = [
        'area_sqkm' => 'decimal:2',
        'center_lat' => 'decimal:7',
        'center_lon' => 'decimal:7',
        'geometry' => 'array',
        'properties' => 'array',
    ];

    /**
     * Get the province that owns this territoire
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get all communes for this territoire
     */
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }
}
