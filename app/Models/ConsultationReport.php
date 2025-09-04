<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ConsultationReport extends Model
{
    protected $fillable = [
        'consultation_id','guru_bk_id','siswa_id',
        'summary','follow_up','private_notes',
        'started_at','ended_at','duration_minutes',
        'student_visible','acknowledged_at',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'acknowledged_at'  => 'datetime',
        'student_visible'  => 'boolean',
    ];

    public function consultation() { return $this->belongsTo(Consultation::class); }
    public function guruBK()       { return $this->belongsTo(User::class, 'guru_bk_id'); }
    public function siswa()        { return $this->belongsTo(User::class, 'siswa_id'); }
}
