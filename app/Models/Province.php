<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pcode',
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
     * Get all territoires for this province
     */
    public function territoires(): HasMany
    {
        return $this->hasMany(Territoire::class);
    }

    /**
     * Get all communes for this province
     */
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }
}
