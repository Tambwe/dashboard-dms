<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePushNotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'mobile_push_notification_id',
        'mobile_device_id',
        'token_snapshot',
        'status',
        'ticket_id',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(MobilePushNotification::class, 'mobile_push_notification_id');
    }

    public function device()
    {
        return $this->belongsTo(MobileDevice::class, 'mobile_device_id');
    }
}
