<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'organisation_id',
        'role',
        'is_active',
        'phone',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    /**
     * Get the organisation that the user belongs to.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function mobileDevices()
    {
        return $this->hasMany(MobileDevice::class);
    }

    /**
     * Get the sites that the user has access to (for data collection).
     */
    public function assignedSites()
    {
        return $this->belongsToMany(Site::class, 'site_user_access')
            ->withPivot(['can_edit', 'can_collect', 'granted_at', 'granted_by'])
            ->withTimestamps();
    }

    /**
     * Check if user has access to a specific site.
     */
    public function hasAccessToSite(Site $site): bool
    {
        // Super admin has access to all sites
        if ($this->isSuperAdmin() || $this->isSigUser()) {
            return true;
        }

        // Admin organisation has access to organisation's sites
        if ($this->isAdminOrganisation() && $site->organisation_id === $this->organisation_id) {
            return true;
        }

        // Check if user has explicit access
        return $this->assignedSites()->where('sites.id', $site->id)->exists();
    }

    /**
     * Check if user can edit a specific site.
     */
    public function canEditSite(Site $site): bool
    {
        // Super admin can edit all sites
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Admin organisation can edit organisation's sites
        if ($this->isAdminOrganisation() && $site->organisation_id === $this->organisation_id) {
            return true;
        }

        // Check pivot table for can_edit permission
        $access = $this->assignedSites()->where('sites.id', $site->id)->first();
        return $access && $access->pivot->can_edit;
    }

    /**
     * Check if user can update site media data (GeoJSON layers and photos).
     */
    public function canEditSiteMedia(Site $site): bool
    {
        if ($this->isSigUser()) {
            return $this->hasAccessToSite($site);
        }

        return $this->canEditSite($site);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        if (Schema::hasColumn('users', 'role') && ($this->role ?? null) === 'super_admin') {
            return true;
        }

        return strcasecmp((string) ($this->email ?? ''), 'superadmin@dms-cccm.org') === 0;
    }

    /**
     * Check if user is organisation admin.
     */
    public function isAdminOrganisation(): bool
    {
        return $this->role === 'admin_organisation';
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user is SIG user.
     */
    public function isSigUser(): bool
    {
        return $this->role === 'sig_user';
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isAdminOrganisation();
    }

    /**
     * Get users that current user can manage.
     */
    public function getManagedUsersQuery()
    {
        if ($this->isSuperAdmin()) {
            return User::query();
        }

        if ($this->isAdminOrganisation()) {
            return User::where('organisation_id', $this->organisation_id)
                ->where('id', '!=', $this->id);
        }

        return User::whereRaw('1 = 0'); // No users for regular users
    }
}
