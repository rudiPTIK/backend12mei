<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiasecTestCareer extends Model
{
    protected $fillable = [
        'riasec_test_id','title','onet_code','rank','meta',
    ];

    public function test()
    {
        return $this->belongsTo(RiasecTest::class, 'riasec_test_id');
    }
}
