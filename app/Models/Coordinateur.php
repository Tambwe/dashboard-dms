<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordinateur extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'telephone',
    ];

    /**
     * Relation : Un coordinateur peut coordonner plusieurs sites
     */
    public function sites()
    {
        return $this->hasMany(Site::class, 'coordinateur_id');
    }
}
