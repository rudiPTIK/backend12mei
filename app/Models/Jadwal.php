<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Laravel\Sanctum\HasApiTokens;
    use Illuminate\Notifications\Notifiable;

    class Jadwal extends Model
    {
        use HasFactory, HasApiTokens, Notifiable;

        protected $table ='jadwal';

        protected $fillable =[
            'guru_bk_id',
            'siswa_id',
            'waktu_mulai',
            'waktu_selesai',
            'role',
            'status'
        ];
        public function guruBK(){
            return $this->belongsTo(User::class, 'guru_bk_id');
        }
        public function siswa(){
            return $this->belongsTo(User::class, 'siswa_id');
        }
    }
