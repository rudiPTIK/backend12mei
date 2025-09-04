<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\ConsultationReport;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ConsultationController extends Controller
{
    /**
     * GET /consultations
     * - Guru BK: default miliknya (counselor_id = Auth::id()).
     * - Siswa: di-force student_id = Auth::id().
     * Query opsional: student_id (diabaikan utk siswa), jadwal_id, limit
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = Consultation::with([
            'student:id,name,email',
            'counselor:id,name,email',
            'jadwal:id,waktu_mulai,waktu_selesai,status',
            'report',
        ])->latest('id');

        if (strtolower($user->role) === 'siswa') {
            $q->where('student_id', $user->id);
        } else {
            if ($request->filled('student_id')) {
                $q->where('student_id', (int) $request->student_id);
            } else {
                $q->where('counselor_id', $user->id);
            }
        }

        if ($request->filled('jadwal_id')) {
            $q->where('jadwal_id', (int) $request->jadwal_id);
        }

        if ($request->filled('limit')) {
            $q->limit((int) $request->limit);
        }

        return response()->json($q->get());
    }

    /**
     * POST /consultations
     * Body: { "jadwal_id": <id>, "mode": "video|chat|offline", "topic": "..." }
     * Guru BK membuat sesi dari jadwal yang sudah dipesan siswa.
     */
    public function store(Request $request)
    {
        $request->validate([
            'jadwal_id' => 'required|exists:jadwal,id',
            'mode'      => 'nullable|string|in:video,chat,offline',
            'topic'     => 'nullable|string|max:255',
        ]);

        // Pastikan jadwal milik guru BK login
        $jadwal = Jadwal::with('siswa')
            ->where('id', $request->jadwal_id)
            ->where('guru_bk_id', Auth::id())
            ->firstOrFail();

        if (!$jadwal->siswa_id) {
            return response()->json(['message' => 'Jadwal belum dipesan siswa'], 422);
        }

        // Cegah duplikasi consultation untuk jadwal yang sama
        $existing = Consultation::where('jadwal_id', $jadwal->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Konsultasi sudah dibuat untuk jadwal ini',
                'data'    => $existing
            ], 200);
        }

        $consult = Consultation::create([
            'student_id'   => $jadwal->siswa_id,
            'counselor_id' => $jadwal->guru_bk_id,
            'jadwal_id'    => $jadwal->id,
            'mode'         => $request->mode ?? 'video',
            'topic'        => $request->topic,
            'status'       => 'scheduled', // konsisten dgn migrasi
        ]);

        return response()->json([
            'message' => 'Konsultasi berhasil dibuat',
            'data'    => $consult
        ], 201);
    }

    /**
     * POST /consultations/{id}/start
     * Hanya konselor pemilik
     */
    public function start($id)
    {
        $consult = Consultation::findOrFail($id);

        if ($consult->counselor_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden (not your consultation)'], 403);
        }

        if ($consult->status === 'ended') {
            return response()->json(['message' => 'Sesi sudah selesai'], 422);
        }

        $consult->update([
            'started_at' => $consult->started_at ?: Carbon::now(),
            'status'     => 'ongoing',
        ]);

        return response()->json(['message' => 'Sesi dimulai', 'data' => $consult], 200);
    }

    /**
     * POST /consultations/{id}/end
     * Akhiri konsultasi + auto-generate laporan
     * Body opsional: { "notes": "...", "follow_up": "..." }
     * Hanya konselor pemilik
     */
    public function end(Request $request, $id)
    {
        $request->validate([
            'notes'     => 'nullable|string',
            'follow_up' => 'nullable|string',
        ]);

        $consult = Consultation::with(['student','counselor','jadwal'])->findOrFail($id);

        if ($consult->counselor_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden (not your consultation)'], 403);
        }

        DB::transaction(function () use ($request, $consult) {
            $now     = Carbon::now();
            $started = $consult->started_at ?: $now;

            // update sesi
            $consult->update([
                'notes'    => $request->input('notes', $consult->notes),
                'ended_at' => $now,
                'status'   => 'ended',
            ]);

            // opsional: tandai jadwal selesai
            if ($consult->jadwal) {
                $consult->jadwal->update(['status' => 'selesai']);
            }

            // hitung durasi (menit)
            $duration = Carbon::parse($started)->diffInMinutes($now);

            // buat/ubah laporan
            ConsultationReport::updateOrCreate(
                ['consultation_id' => $consult->id],
                [
                    'guru_bk_id'       => $consult->counselor_id,
                    'siswa_id'         => $consult->student_id,
                    'summary'          => $request->input('notes', $consult->notes) ?: '—',
                    'follow_up'        => $request->input('follow_up'),
                    'started_at'       => $started,
                    'ended_at'         => $now,
                    'duration_minutes' => $duration,
                ]
            );
        });

        $consult->load('report');

        return response()->json([
            'message' => 'Sesi diakhiri & laporan otomatis dibuat',
            'data'    => $consult
        ], 200);
    }

    /**
     * GET /consultations/{id}/report
     * Konselor / Siswa pemilik sesi
     */
  public function report($id)
{
    $consult = \App\Models\Consultation::with('report')->findOrFail($id);

    $userId = Auth::id();
    $isOwnerCounselor = ($consult->counselor_id === $userId);
    $isOwnerStudent   = ($consult->student_id   === $userId);

    if (!$isOwnerCounselor && !$isOwnerStudent) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // siswa tidak boleh melihat jika disembunyikan
    if ($isOwnerStudent && $consult->report && $consult->report->student_visible === false) {
        return response()->json(['message' => 'Report is hidden from student'], 403);
    }

    return response()->json([
        'consultation_id' => $consult->id,
        'report'          => $consult->report
    ], 200);
}


    /**
     * POST /consultations/{id}/report
     * Body: { summary?, follow_up? }
     * Hanya konselor pemilik sesi
     */
  public function storeReport(Request $request, $id)
{
    $request->validate([
        'summary'         => 'nullable|string',
        'follow_up'       => 'nullable|string',
        'private_notes'   => 'nullable|string',
        'student_visible' => 'nullable|boolean',
    ]);

    $consult = Consultation::findOrFail($id);
    if ($consult->counselor_id !== Auth::id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $report = ConsultationReport::firstOrCreate(
        ['consultation_id' => $consult->id],
        [
            'guru_bk_id' => $consult->counselor_id,
            'siswa_id'   => $consult->student_id,
        ]
    );

    $report->update([
        'summary'         => $request->input('summary', $report->summary),
        'follow_up'       => $request->input('follow_up', $report->follow_up),
        'private_notes'   => $request->input('private_notes', $report->private_notes),
        'student_visible' => $request->boolean('student_visible', $report->student_visible ?? true),
    ]);

    return response()->json(['message' => 'Laporan diperbarui', 'data' => $report], 200);
}


    /** GET /reports (list laporan milik Guru BK) */
    public function listReports()
    {
        $guruId = Auth::id();
        $reports = ConsultationReport::with([
            'consultation:id,topic,mode,status',
            'siswa:id,name',
        ])->where('guru_bk_id', $guruId)
          ->latest('id')
          ->get();

        return response()->json($reports, 200);
    }

    /** GET /my-reports (list laporan milik siswa login) */
   public function myReportsForStudent()
{
    $siswaId = Auth::id();
    $reports = \App\Models\ConsultationReport::with([
        'consultation:id,topic,mode,status,started_at,ended_at',
        'guruBK:id,name',
    ])
    ->where('siswa_id', $siswaId)
    ->where('student_visible', true) // ⬅️ filter penting
    ->latest('id')
    ->get();

    return response()->json($reports, 200);
}

    // acknowledge oleh siswa
public function acknowledgeReport($id)
{
    $consult = Consultation::with('report')->findOrFail($id);

    if ($consult->student_id !== Auth::id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $report = $consult->report;
    if (!$report) {
        return response()->json(['message' => 'Report not found'], 404);
    }
    // jika disembunyikan dari siswa, jangan izinkan ack
    if ($report->student_visible === false) {
        return response()->json(['message' => 'Report not visible to student'], 403);
    }

    $report->update(['acknowledged_at' => now()]);
    return response()->json(['message' => 'Acknowledged', 'data' => $report], 200);
}
public function setVisibility(Request $request, $id)
{
    $request->validate([
        'student_visible' => 'required|boolean',
    ]);

    $consult = \App\Models\Consultation::findOrFail($id);
    if ($consult->counselor_id !== Auth::id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $report = \App\Models\ConsultationReport::firstOrCreate(
        ['consultation_id' => $consult->id],
        ['guru_bk_id' => $consult->counselor_id, 'siswa_id' => $consult->student_id]
    );

    $report->update(['student_visible' => (bool) $request->student_visible]);

    return response()->json([
        'message' => 'Visibility updated',
        'data'    => ['student_visible' => $report->student_visible],
    ], 200);
}
public function acknowledge($id)
{
    $consult = \App\Models\Consultation::with('report')->findOrFail($id);

    if ($consult->student_id !== Auth::id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    if (!$consult->report) {
        return response()->json(['message' => 'Report not found'], 404);
    }
    if ($consult->report->student_visible === false) {
        return response()->json(['message' => 'Report is hidden'], 403);
    }

    $consult->report->update(['acknowledged_at' => now()]);

    return response()->json(['message' => 'Acknowledged'], 200);
}


}
