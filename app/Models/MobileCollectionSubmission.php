<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileCollectionSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'site_id',
        'type',
        'sector',
        'payload',
        'status',
        'sync_error',
        'synced_at',
        'validation_status',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function geographyEntry()
    {
        return $this->hasOne(SiteGeography::class, 'mobile_collection_submission_id');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
