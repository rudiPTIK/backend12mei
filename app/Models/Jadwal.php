<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwal'; // ⬅️ PENTING

    protected $fillable = [
        'guru_bk_id','siswa_id','waktu_mulai','waktu_selesai','status','link_konseling'
    ];

    public function guruBK() { return $this->belongsTo(User::class, 'guru_bk_id'); }
    public function siswa()  { return $this->belongsTo(User::class, 'siswa_id'); }

    public function consultation() { return $this->hasOne(Consultation::class, 'jadwal_id'); }
}
