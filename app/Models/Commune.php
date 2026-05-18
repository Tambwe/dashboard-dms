<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pcode',
        'territoire_id',
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
     * Get the territoire that owns this commune
     */
    public function territoire(): BelongsTo
    {
        return $this->belongsTo(Territoire::class);
    }

    /**
     * Get the province that owns this commune
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
