<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'expo_push_token',
        'device_name',
        'platform',
        'app_version',
        'notifications_enabled',
        'last_login_at',
        'last_notification_at',
        'last_error',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'last_notification_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(MobilePushNotificationDelivery::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query
            ->where('notifications_enabled', true)
            ->whereNotNull('expo_push_token');
    }
}
