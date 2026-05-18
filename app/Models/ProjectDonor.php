<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectDonor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_donor_project');
    }
}
