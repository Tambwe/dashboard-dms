<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Relation : Un type de site peut avoir plusieurs sites
     */
    public function sites()
    {
        return $this->hasMany(Site::class, 'type_site_id');
    }
}
