<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'body',
        'data',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries()
    {
        return $this->hasMany(MobilePushNotificationDelivery::class);
    }
}
