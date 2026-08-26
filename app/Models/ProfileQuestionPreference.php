<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileQuestionPreference extends Model
{
    protected $fillable = [
        'user_id',
        'questionnaire_id',
        'visible_question_keys',
    ];

    protected $casts = [
        'visible_question_keys' => 'array',
    ];
}
