<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\User;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;

class JadwalController extends Controller
{
    // ======== SISWA ========

    // Siswa memilih jadwal yang masih "tersedia"
    public function pilihJadwal($id)
    {
        $jadwal = Jadwal::findOrFail($id);

        if ($jadwal->status !== 'tersedia') {
            return response()->json(['message' => 'Jadwal sudah dipesan / tidak tersedia'], 400);
        }

        $jadwal->update([
            'siswa_id' => Auth::id(),
            'status'   => 'dipesan',
        ]);

        return response()->json(['message' => 'Jadwal berhasil dipilih'], 200);
    }

    // List jadwal yang tersedia (untuk siswa memilih)
    public function getJadwal()
    {
        $jadwal = Jadwal::where('status', 'tersedia')->orderBy('waktu_mulai')->get();
        return response()->json($jadwal);
    }

    // Jadwal milik siswa login
    public function jadwalSaya()
    {
        $siswaId = Auth::id();

        $jadwal = Jadwal::with(['guruBK:id,name,email'])
            ->where('siswa_id', $siswaId)
            ->orderBy('waktu_mulai')
            ->get();

        return response()->json([
            'message' => 'Berhasil ambil jadwal siswa',
            'data'    => $jadwal,
        ]);
    }

    // Siswa membatalkan jadwalnya
    public function batalJadwal($id)
    {
        $jadwal = Jadwal::where('id', $id)
            ->where('siswa_id', Auth::id())
            ->first();

        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan atau bukan milik Anda'], 404);
        }

        $jadwal->update([
            'siswa_id' => null,
            'status'   => 'tersedia',
        ]);

        return response()->json(['message' => 'Jadwal berhasil dibatalkan'], 200);
    }

    // ======== GURU BK ========

    // Jadwal yang sudah dipesan (tampilkan consultation_id jika ada)
    public function getJadwalDipesan()
    {
        $jadwal = Jadwal::with(['siswa:id,name,email', 'consultation:id,jadwal_id'])
            ->whereNotNull('siswa_id')
            ->orderBy('waktu_mulai')
            ->get()
            ->map(function ($j) {
                return [
                    'id'              => $j->id,
                    'waktu_mulai'     => $j->waktu_mulai,
                    'waktu_selesai'   => $j->waktu_selesai,
                    'status'          => $j->status,
                    'siswa'           => $j->siswa,
                    'consultation_id' => optional($j->consultation)->id,
                ];
            });

        return response()->json($jadwal);
    }

    // List jadwal milik Guru BK login
    public function index()
    {
        $guruId = Auth::id();

        $jadwal = Jadwal::with('siswa:id,name')
            ->where('guru_bk_id', $guruId)
            ->orderBy('waktu_mulai')
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'tanggal'     => date('Y-m-d', strtotime($item->waktu_mulai)),
                    'waktu_mulai' => date('H:i', strtotime($item->waktu_mulai)),
                    'status'      => $item->status,
                    'nama_siswa'  => optional($item->siswa)->name,
                ];
            });

        return response()->json($jadwal);
    }

    // Tambah jadwal (Guru BK)
    public function store(Request $request)
    {
        $request->validate([
            'waktu_mulai'   => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
        ]);

        $jadwal = Jadwal::create([
            'guru_bk_id'   => Auth::id(),
            'waktu_mulai'  => $request->waktu_mulai,
            'waktu_selesai'=> $request->waktu_selesai,
            'status'       => 'tersedia',
        ]);

        return response()->json(['message' => 'Jadwal berhasil ditambahkan', 'data' => $jadwal], 201);
    }

    // Ubah jadwal (Guru BK)
    public function update(Request $request, $id)
    {
        $request->validate([
            'waktu_mulai'   => 'required|date',
            'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            'status'        => 'required|in:tersedia,dipesan,selesai',
        ]);

        $jadwal = Jadwal::where('id', $id)
            ->where('guru_bk_id', Auth::id())
            ->first();

        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $jadwal->update([
            'waktu_mulai'  => $request->waktu_mulai,
            'waktu_selesai'=> $request->waktu_selesai,
            'status'       => $request->status,
        ]);

        return response()->json(['message' => 'Jadwal berhasil diperbarui', 'data' => $jadwal], 200);
    }

    // Hapus jadwal (Guru BK)
    public function destroy($id)
    {
        $jadwal = Jadwal::where('id', $id)
            ->where('guru_bk_id', Auth::id())
            ->first();

        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $jadwal->delete();
        return response()->json(['message' => 'Jadwal berhasil dihapus']);
    }

    // Lihat detail siswa pada jadwal tertentu (Guru BK)
    public function daftarsiswa($id)
    {
        $jadwal = Jadwal::with('siswa:id,name,email')->findOrFail($id);
        return response()->json($jadwal);
    }

    // ======== AGORA RTC TOKEN (Guru BK & Siswa yang TERLIBAT) ========

    public function generateRtcToken($jadwalId)
    {
        $userId = Auth::id();

        // Ambil jadwal & cek partisipasi user
        $jadwal = Jadwal::select('id', 'guru_bk_id', 'siswa_id', 'status')
            ->findOrFail($jadwalId);

        // Hanya peserta yang boleh minta token
        $isCounselor = ($jadwal->guru_bk_id === $userId);
        $isStudent   = ($jadwal->siswa_id   === $userId);

        if (!$isCounselor && !$isStudent) {
            return response()->json(['message' => 'Forbidden: bukan peserta jadwal'], 403);
        }

        // Wajib sudah dipesan (bukan "tersedia")
        if ($jadwal->status !== 'dipesan' && $jadwal->status !== 'selesai') {
            return response()->json(['message' => 'Jadwal belum dipesan'], 422);
        }

        // Kredensial dari config/services.php (pastikan sudah diset)
        $appId   = (string) config('services.agora.app_id');
        $appCert = (string) config('services.agora.app_certificate');

        if (empty($appId) || empty($appCert)) {
            Log::error('AGORA_APP_ID or AGORA_APP_CERTIFICATE is missing');
            return response()->json(['error' => 'Server misconfiguration'], 500);
        }

        $channel  = 'jadwal_' . $jadwal->id;
        $uid      = $userId; // gunakan user id sebagai uid (atau mapping sendiri)
        $expireTs = Carbon::now()->addDay()->timestamp;

        try {
            $token = (string) RtcTokenBuilder2::buildTokenWithUid(
                $appId,
                $appCert,
                $channel,
                (int) $uid,
                RtcTokenBuilder2::ROLE_PUBLISHER,
                $expireTs,
                $expireTs
            );
        } catch (\Throwable $e) {
            Log::error('Error generating Agora token: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        if (empty($token)) {
            Log::error('Generated token is empty');
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        Log::info('Generated Agora token length: ' . strlen($token) . " for user {$userId}");

        return response()->json([
            'channel' => $channel,
            'uid'     => (int) $uid,
            'token'   => $token,
        ], 200);
    }
}
