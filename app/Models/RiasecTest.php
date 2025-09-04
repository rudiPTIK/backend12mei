<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiasecTest extends Model
{
    protected $fillable = [
        'user_id', 'answers', 'scores',
        'r','i','a','s','e','c','code3',
        'client','ip','user_agent',
    ];

    protected $casts = [
        'scores' => 'array',
    ];

    public function careers()
    {
        return $this->hasMany(\App\Models\RiasecTestCareer::class, 'riasec_test_id');
    }
}
