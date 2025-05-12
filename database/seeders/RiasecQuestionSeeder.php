<?php
// database/seeders/RiasecQuestionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiasecQuestionSeeder extends Seeder
{
    public function run()
    {
        $questions = [
            // — Realistic (8)
            ['Saya suka merakit atau memperbaiki mesin dan peralatan.', 'R'],
            ['Saya senang bekerja dengan alat dan mesin berat.', 'R'],
            ['Saya menikmati aktivitas fisik di luar ruangan.', 'R'],
            ['Saya tertarik memperbaiki kendaraan atau peralatan rumah.', 'R'],
            ['Saya nyaman bekerja dengan bahan bangunan atau konstruksi.', 'R'],
            ['Saya merasa puas saat membuat atau memperbaiki sesuatu secara praktis.', 'R'],
            ['Saya tertarik bekerja di bidang teknik dan mekanik.', 'R'],
            ['Saya suka menggunakan alat-alat tangan dalam proyek.', 'R'],
            // — Investigative (8)
            ['Saya suka menganalisis data dan mencari pola.', 'I'],
            ['Saya menikmati melakukan eksperimen dan penelitian.', 'I'],
            ['Saya merasa tertantang memecahkan soal matematika atau sains.', 'I'],
            ['Saya senang membaca artikel ilmiah atau teknis.', 'I'],
            ['Saya suka mengumpulkan fakta dan informasi.', 'I'],
            ['Saya tertarik pada riset laboratorium.', 'I'],
            ['Saya puas saat menemukan solusi lewat logika.', 'I'],
            ['Saya merasa penasaran dengan fenomena alam.', 'I'],
            // — Artistic (8)
            ['Saya suka melukis, menggambar, atau mendesain.', 'A'],
            ['Saya tertarik menulis cerita, puisi, atau naskah kreatif.', 'A'],
            ['Saya menikmati bermain musik atau menciptakan lagu.', 'A'],
            ['Saya senang berekspresi melalui tarian atau drama.', 'A'],
            ['Saya suka memotret atau membuat video artistik.', 'A'],
            ['Saya tertantang merancang tata letak atau dekorasi.', 'A'],
            ['Saya merasa puas saat menciptakan sesuatu yang orisinal.', 'A'],
            ['Saya menikmati eksperimen dalam seni rupa.', 'A'],
            // — Social (8)
            ['Saya senang membantu orang mengatasi masalah mereka.', 'S'],
            ['Saya tertarik mengajar atau melatih orang lain.', 'S'],
            ['Saya merasa puas saat bekerja dalam tim untuk tujuan sosial.', 'S'],
            ['Saya nyaman mendengarkan dan memberikan dukungan emosional.', 'S'],
            ['Saya suka terlibat dalam kegiatan relawan atau layanan masyarakat.', 'S'],
            ['Saya menikmati memfasilitasi diskusi kelompok.', 'S'],
            ['Saya tertarik pada profesi konseling atau terapi.', 'S'],
            ['Saya senang berinteraksi langsung dengan banyak orang.', 'S'],
            // — Enterprising (8)
            ['Saya tertarik memimpin proyek atau tim kerja.', 'E'],
            ['Saya suka bernegosiasi atau menjual ide/produk.', 'E'],
            ['Saya menikmati mengorganisir acara atau kampanye.', 'E'],
            ['Saya percaya diri mengambil keputusan bisnis.', 'E'],
            ['Saya senang memotivasi orang untuk bertindak.', 'E'],
            ['Saya tertantang mencapai target penjualan.', 'E'],
            ['Saya merasa puas saat mengatur strategi promosi.', 'E'],
            ['Saya suka mengelola dan mendorong tim menuju tujuan.', 'E'],
            // — Conventional (8)
            ['Saya nyaman mengelola data dan dokumen administratif.', 'C'],
            ['Saya tertarik menyusun laporan dan arsip.', 'C'],
            ['Saya suka menggunakan spreadsheet untuk mengatur angka.', 'C'],
            ['Saya menikmati mengikuti prosedur dan aturan baku.', 'C'],
            ['Saya merasa puas saat merapikan dan mengorganisir file.', 'C'],
            ['Saya senang bekerja dengan sistem dan catatan terstruktur.', 'C'],
            ['Saya tertantang menjaga akurasi dalam entri data.', 'C'],
            ['Saya suka mengoperasikan peralatan kantor seperti printer atau scanner.', 'C'],
        ];

        foreach ($questions as [$text, $type]) {
            DB::table('riasec_questions')->insert([
                'text'        => $text,
                'riasec_type' => $type,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
