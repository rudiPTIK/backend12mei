<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;

class JadwalController extends Controller

{

    //siswa memilih jadwal
     public function pilihJadwal($id) {
          // Validasi Input
          $jadwal = Jadwal::findOrFail($id);
    
        
            // Cek apakah jadwal sudah dipesan oleh siswa lain
            if($jadwal->status !=='tersedia'){
                return response()->json(['message' =>'Jadwal sudah dipesan'], 400);
            }
            
        
            // Update status jadwal
            $jadwal->update([
                'siswa_id' => Auth::id(),
                'status' => 'dipesan'
            ]);
        
            return response()->json(['message' => 'Jadwal berhasil dipilih'], 200);
        }
    //menampilkan jadwal yang tersedia
    public function getJadwal(){
        $jadwal = Jadwal::where('status', 'tersedia')->get();
        return response()->json($jadwal);
    }
    public function jadwalSaya()
    {
        $siswaId = Auth::id();
    
        $jadwal = Jadwal::with(['guruBK' => function ($q) {
            $q->select('id', 'name', 'email'); // select kolom yang dibutuhkan
        }])->where('siswa_id', $siswaId)->get();
    
        return response()->json([
            'message' => 'Berhasil ambil jadwal siswa',
            'data' => $jadwal
        ]);
    }
    
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
            'status' => 'tersedia'
        ]);
    
        return response()->json(['message' => 'Jadwal berhasil dibatalkan'], 200);
    }
    

    
    //guru bk melihat jadwal yang sudah dipesan
    public function getJadwalDipesan(){
        $jadwal = Jadwal::whereNotNull('siswa_id')->with('siswa')->get();
        return response()->json($jadwal);
    }
    public function index()
    {
        $guruId = Auth::id();   // id guru BK yang sedang login
    
        $jadwal = Jadwal::with('siswa')        // relasi siswa
            ->where('guru_bk_id', $guruId)     // ⬅️ filter milik guru ini saja
            ->orderBy('waktu_mulai')
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id, 
                    'tanggal'     => date('Y-m-d', strtotime($item->waktu_mulai)),
                    'waktu_mulai' => date('H:i',   strtotime($item->waktu_mulai)),
                    'status'      => $item->status,
                    'nama_siswa'  => optional($item->siswa)->name,   // atau ->name
                ];
            });
    
        return response()->json($jadwal);
    }
    
       
       
    //tambah jadwal

    public function store(Request $request) {
        $request->validate([
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
        ]);

        $jadwal = Jadwal::create([
            'guru_bk_id' => Auth::id(),
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'status' => 'tersedia',
        ]);

        return response()->json(['message' => 'Jadwal Berhasil ditambahkan','data'=>$jadwal], 201);
    }

 
    public function update(Request $request, $id)
    {
        // Validasi data yang dikirimkan
        $request->validate([
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date',
            'status' => 'required|string',
        ]);
    
        // Cari jadwal berdasarkan ID
        $jadwal = Jadwal::find($id);
    
        // Jika tidak ditemukan
        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }
    
        // Update jadwal dengan data baru
        $jadwal->update([
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'status' => $request->status,
        ]);
    
        // Kembalikan response berhasil
        return response()->json(['message' => 'Jadwal berhasil diperbarui', 'data' => $jadwal], 200);
    }
    


    
    //guru bk menghapus jadwal
    public function destroy($id){
        $jadwal = Jadwal::where('id', $id)->where('guru_bk_id', Auth::id())->first();
        if(!$jadwal){
            return response()->json(['message'=> 'jadwal tidak ditemukan'], 404);
        }
        $jadwal->delete();
        return response()->json(['message' => 'jadwal berhasil dihapus']);
    }

    //melihat siswa yang memilih jadwal tertentu
    public function daftarsiswa($id){
        $jadwal = Jadwal::with('siswa')->findOrFail($id);
        return response()->json($jadwal);
    }

    public function generateRtcToken($jadwalId)
    {
        $appId       = env('AGORA_APP_ID');
        $appCert     = env('AGORA_APP_CERTIFICATE');
        $channelName = 'jadwal_' . $jadwalId;
        $uid         = Auth::id();
        $expireTs    = Carbon::now()->timestamp + 3600;

        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCert,
            $channelName,
            $uid,
            RtcTokenBuilder2::ROLE_PUBLISHER,
            $expireTs,
            $expireTs
        );

        return response()->json([
            'channel' => $channelName,
            'uid'     => $uid,
            'token'   => $token,
        ], 200);
    }


}
