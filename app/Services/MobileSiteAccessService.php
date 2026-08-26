<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileSiteAccessService
{
    public function accessibleSitesQuery(User $user): Builder
    {
        $query = Site::query();

        if ($user->isSuperAdmin() || $user->isSigUser()) {
            return $query;
        }

        return $query->where(function (Builder $siteQuery) use ($user): void {
            if ($user->organisation_id) {
                $siteQuery->where('organisation_id', $user->organisation_id);
            } else {
                $siteQuery->whereRaw('1 = 0');
            }

            $siteQuery->orWhereHas('assignedUsers', function (Builder $accessQuery) use ($user): void {
                $accessQuery
                    ->where('users.id', $user->id)
                    ->where('site_user_access.can_collect', true);
            });
        });
    }

    public function assertCanCollect(User $user, Site $site): void
    {
        if ($user->isSuperAdmin() || $user->isSigUser()) {
            return;
        }

        if ($user->organisation_id && (int) $site->organisation_id === (int) $user->organisation_id) {
            return;
        }

        $hasExplicitAccess = $user->assignedSites()
            ->where('sites.id', $site->id)
            ->wherePivot('can_collect', true)
            ->exists();

        if (! $hasExplicitAccess) {
            throw new AuthorizationException('Vous n’avez pas accès à ce site pour la collecte mobile.');
        }
    }

    public function assignNewSiteToCollector(Site $site, User $user): void
    {
        $organisationId = $this->collectorOrganisationId($user);

        if ((int) $site->organisation_id !== $organisationId) {
            $site->organisation_id = $organisationId;
            $site->save();
        }

        $user->assignedSites()->syncWithoutDetaching([
            $site->id => [
                'can_edit' => true,
                'can_collect' => true,
                'granted_by' => $user->id,
                'granted_at' => now(),
            ],
        ]);
    }

    public function createSiteForCollector(User $user, array $siteData): Site
    {
        $organisationId = $this->collectorOrganisationId($user);

        return DB::transaction(function () use ($user, $siteData, $organisationId): Site {
            $site = Site::query()->create([
                ...$siteData,
                'organisation_id' => $organisationId,
            ]);
            $this->assignNewSiteToCollector($site, $user);

            return $site;
        });
    }

    private function collectorOrganisationId(User $user): int
    {
        if (! $user->organisation_id) {
            throw ValidationException::withMessages([
                'site' => 'Votre compte doit être rattaché à une organisation pour créer un site.',
            ]);
        }

        return (int) $user->organisation_id;
    }
}
