<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organisation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the organisation.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the sites managed by the organisation.
     */
    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Get the projects managed by the organisation.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the admin users for the organisation.
     */
    public function admins()
    {
        return $this->hasMany(User::class)->where('role', 'admin_organisation');
    }

    /**
     * Get all active users for the organisation.
     */
    public function activeUsers()
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'cluster_organisation')
            ->withTimestamps();
    }
}
