<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id','counselor_id','jadwal_id',
        'mode','topic','notes','started_at','ended_at',
        'status','recording_url'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function student()   { return $this->belongsTo(User::class, 'student_id'); }
    public function counselor() { return $this->belongsTo(User::class, 'counselor_id'); }
    public function jadwal()    { return $this->belongsTo(Jadwal::class, 'jadwal_id'); }
    public function report()    { return $this->hasOne(ConsultationReport::class); }
}
