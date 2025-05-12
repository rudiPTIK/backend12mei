<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiasecQuestion extends Model
{
    protected $fillable = ['text','riasec_type'];

    public function responses()
    {
        return $this->hasMany(RiasecResponse::class);
    }
}
