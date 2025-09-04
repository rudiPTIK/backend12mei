<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name','email','password',
        'phone','gender','birthdate','avatar_path','role'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'birthdate' => 'date',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar_path) return null;
         return asset('storage/' . ltrim($this->avatar_path, '/'));
    }
public function consultationsAsStudent()
{
    return $this->hasMany(Consultation::class, 'student_id');
}
public function consultationsAsCounselor()
{
    return $this->hasMany(Consultation::class, 'counselor_id');
}

   
}
