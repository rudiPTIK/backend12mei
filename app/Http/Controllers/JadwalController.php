<?php

namespace App\Http\Controllers;
use App\Models\RiasecResult;
use App\Models\RiasecCareer;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;

class JadwalController extends Controller

{
    public function __construct()
    {
        // → Tambahkan ini:
        $this->middleware(function ($request, $next) {
            // hapus semua jadwal yang waktu_selesai-nya sudah lewat
            Jadwal::where('waktu_selesai', '<', Carbon::now())->delete();
            return $next($request);
        });
    }
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
       // Ambil kredensial dari config/services.php
       $appId    = config('services.agora.app_id');
       $appCert  = config('services.agora.app_certificate');
       $channel  = 'jadwal_' . $jadwalId;
       $uid      = Auth::id();
       $expireTs = Carbon::now()->addDay()->timestamp;
    
       // Validasi konfigurasi
       if (empty($appId) || empty($appCert)) {
           Log::error('AGORA_APP_ID or AGORA_APP_CERTIFICATE is missing');
           return response()->json(['error' => 'Server misconfiguration'], 500);
       }

       try {
           /** 
            * buildTokenWithUid sudah return value yang bertipe string,
            * tapi untuk Intelephense kita cast sekali lagi menjadi string.
            */
           $token = (string) RtcTokenBuilder2::buildTokenWithUid(
               $appId,
               $appCert,
               $channel,
               $uid,
               RtcTokenBuilder2::ROLE_PUBLISHER,
               $expireTs,
               $expireTs
           );
       } catch (\Throwable $e) {
           Log::error('Error generating Agora token: ' . $e->getMessage());
           return response()->json(['error' => 'Failed to generate token'], 500);
       }

       // Debug: cek panjang token
       Log::info('Generated Agora token length: ' . strlen($token));

       if (empty($token)) {
           Log::error('Generated token is empty');
           return response()->json(['error' => 'Failed to generate token'], 500);
       }

       // Kembalikan response JSON
       return response()->json([
           'channel' => $channel,
           'uid'     => $uid,
           'token'   => $token,
       ], 200);
   }
    
    public function history(Request $request)
    {
        $userId = $request->user()->id;

        // Ambil semua entri RiasecResult untuk user, terbaru dulu
        $results = RiasecResult::where('user_id', $userId)
                              ->orderBy('created_at', 'desc')
                              ->get();

        // Map ke array dengan tanggal, skor, persentase, domain, rekomendasi
        $data = $results->map(function($h) {
            $top2 = explode(',', $h->top_domains);
            $careers = RiasecCareer::whereIn('riasec_type', $top2)
                                   ->get(['name','description','riasec_type']);

            // Hitung persentase lagi (atau simpan di table jika diinginkan)
            $percent = [];
            foreach (['R','I','A','S','E','C'] as $t) {
                $count = DB::table('riasec_questions')->where('riasec_type',$t)->count();
                $max = $count * 5;
                $rawVal = $h->{"score_$t"};
                $percent[$t] = $max ? round($rawVal/$max*100,1) : 0;
            }

            return [
                'date'            => $h->created_at->toDateTimeString(),
                'raw_scores'      => [
                    'R' => $h->score_R,
                    'I' => $h->score_I,
                    'A' => $h->score_A,
                    'S' => $h->score_S,
                    'E' => $h->score_E,
                    'C' => $h->score_C,
                ],
                'percentages'     => $percent,
                'top_domains'     => $top2,
                'recommendations' => $careers->toArray(),
            ];
        });

        return response()->json($data);
    }
}