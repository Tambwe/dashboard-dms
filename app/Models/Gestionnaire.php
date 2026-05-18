<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'telephone',
    ];

    /**
     * Relation : Un gestionnaire peut gérer plusieurs sites
     */
    public function sites()
    {
        return $this->hasMany(Site::class, 'gestionnaire_id');
    }
}
