<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteGeography extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'mobile_collection_submission_id',
        'user_id',
        'geometry_type',
        'latitude',
        'longitude',
        'accuracy_meters',
        'point_category',
        'point_category_other',
        'polygon_category',
        'polygon_block_name',
        'geojson_data',
        'polygon_segment_distances_m',
        'polygon_segment_min_m',
        'polygon_segment_max_m',
        'polygon_segment_avg_m',
        'polygon_perimeter_m',
        'polygon_point_count',
        'collected_at',
        'source',
        'meta',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy_meters' => 'decimal:2',
        'geojson_data' => 'array',
        'polygon_segment_distances_m' => 'array',
        'polygon_segment_min_m' => 'decimal:2',
        'polygon_segment_max_m' => 'decimal:2',
        'polygon_segment_avg_m' => 'decimal:2',
        'polygon_perimeter_m' => 'decimal:2',
        'polygon_point_count' => 'integer',
        'meta' => 'array',
        'collected_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function submission()
    {
        return $this->belongsTo(MobileCollectionSubmission::class, 'mobile_collection_submission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
