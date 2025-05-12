<?php

// database/seeders/RiasecCareerSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiasecCareerSeeder extends Seeder
{
    public function run()
    {
        $careers = [
            ['Teknisi Mekanik','Memperbaiki dan merawat mesin-mesin industri','R'],
            ['Insinyur Sipil','Merancang dan mengawasi konstruksi bangunan','R'],
            ['Peneliti Laboratorium','Meneliti fenomena ilmiah di laboratorium','I'],
            ['Analis Data','Menganalisis dataset untuk insight bisnis','I'],
            ['Desainer Grafis','Menciptakan karya visual untuk berbagai media','A'],
            ['Penulis Kreatif','Menulis naskah, cerita, atau konten kreatif','A'],
            ['Konsel    or Pendidikan','Membantu siswa mengatasi masalah belajar','S'],
            ['Guru ','Memberikan bimbingan karir dan pribadi kepada siswa','S'],
            ['Manajer Proyek','Memimpin tim untuk menyelesaikan proyek','E'],
            ['Sales Executive','Menjual produk dan layanan kepada klien','E'],
            ['Staf Administrasi','Mengelola dokumen dan data kantor secara terstruktur','C'],
            ['Akuntan','Mencatat dan memeriksa transaksi keuangan','C'],
        ];

        foreach ($careers as [$name,$desc,$type]) {
            DB::table('riasec_careers')->insert([
                'name'         => $name,
                'description'  => $desc,
                'riasec_type'  => $type,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
