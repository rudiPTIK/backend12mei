<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiasecQuestion extends Model
{
    protected $table = 'riasec_questions';
    protected $fillable = [
        'question_id',
        'question_text',
        'category',
    ];
}
