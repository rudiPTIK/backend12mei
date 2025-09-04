<?php

namespace App\Http\Controllers;

use App\Models\AggregateReport;
use App\Models\AggregateReportRecipient;
use App\Models\ConsultationReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AggregateReportController extends Controller
{
    /** GET /aggregate-reports/mine (Guru BK) */
    public function mine(Request $req)
    {
        $uid = Auth::id();

        $list = AggregateReport::withCount('recipients')
            ->where('counselor_id', $uid)
            ->latest('period_start')
            ->get();

        return response()->json($list, 200);
    }

    /** GET /aggregate-reports/inbox (Waka/Kepsek) */
public function inbox(Request $req)
{
    $uid = Auth::id();

    $list = AggregateReportRecipient::with([
            'report' => fn($q) => $q->with('counselor:id,name')->select([
                'id','counselor_id','period_type','period_start','period_end','summary','sent_at'
            ]),
            'sender:id,name', // ⬅️ sekarang valid
        ])
        ->where('recipient_id', $uid)
        ->latest('sent_at') // ini mengacu ke kolom recipients.sent_at
        ->get();

    return response()->json($list, 200);
}


    /**
     * POST /aggregate-reports/compose
     * body: {
     *   period_type: 'daily'|'weekly',
     *   period_start: '2025-09-01',
     *   period_end?: '2025-09-07',
     *   note?: string   // catatan internal rekap
     * }
     */
public function compose(Request $req)
{
    $req->validate([
        'period_type'  => 'required|in:daily,weekly',
        'period_start' => 'required|date',
        'period_end'   => 'nullable|date|after_or_equal:period_start',
        'note'         => 'nullable|string',
    ]);

    $uid   = Auth::id();
    $start = \Carbon\Carbon::parse($req->period_start)->startOfDay();
    $end   = $req->filled('period_end')
        ? \Carbon\Carbon::parse($req->period_end)->endOfDay()
        : ($req->period_type === 'daily'
              ? $start->copy()->endOfDay()
              : $start->copy()->addDays(6)->endOfDay()); // 7 hari

    // Ambil data dari consultation_reports seperti sebelumnya...
    $rows = \App\Models\ConsultationReport::with([
            'consultation:id,student_id,counselor_id,topic,mode,status',
            'consultation.student:id,name',
        ])
        ->whereBetween('started_at', [$start, $end])
        ->whereHas('consultation', fn($q) => $q->where('counselor_id', $uid))
        ->orderBy('started_at')
        ->get();

    $sessionCount   = $rows->count();
    $totalMinutes   = (int) $rows->sum('duration_minutes');
    $uniqueStudents = $rows->pluck('consultation.student_id')->unique()->count();

    $itemsPayload = [
        'metrics' => [
            'sessions'        => $sessionCount,
            'total_minutes'   => $totalMinutes,
            'unique_students' => $uniqueStudents,
        ],
        'items' => $rows->map(function ($r) {
            return [
                'consultation_id' => $r->consultation_id,
                'student_id'      => $r->consultation->student_id,
                'student_name'    => $r->consultation->student->name ?? '-',
                'topic'           => $r->consultation->topic,
                'mode'            => $r->consultation->mode,
                'duration'        => (int) $r->duration_minutes,
                'started_at'      => $r->started_at,
                'ended_at'        => $r->ended_at,
                'summary'         => $r->summary,
                'follow_up'       => $r->follow_up,
            ];
        })->values(),
    ];

    $summaryText = "Rekap {$sessionCount} sesi, {$uniqueStudents} siswa, {$totalMinutes} menit";

    // ⚠️ Kunci unik HARUS pakai toDateString() agar match tepat dengan kolom DATE
    $identity = [
        'counselor_id' => $uid,
        'period_type'  => $req->period_type,
        'period_start' => $start->toDateString(),
        'period_end'   => $end->toDateString(),
    ];

    $updates = [
        'summary'        => $summaryText,
        'items'          => $itemsPayload,     // json
        'total_sessions' => $sessionCount,
        'total_students' => $uniqueStudents,
        'note'           => $req->input('note'),
        // JANGAN sentuh 'sent_at' di sini; biarkan jika sudah pernah kirim
    ];

    $report = \App\Models\AggregateReport::updateOrCreate($identity, $updates);

    return response()->json($report->fresh(), $report->wasRecentlyCreated ? 201 : 200);
}



    /** GET /aggregate-reports/{report} (owner atau penerima) */
    public function show($id)
    {
        $uid = Auth::id();

        $report = AggregateReport::with([
                'counselor:id,name',
                'recipients.recipient:id,name,role',
            ])->findOrFail($id);

        $isOwner = $report->counselor_id === $uid;
        $isRecipient = $report->recipients()->where('recipient_id', $uid)->exists();

        if (!$isOwner && !$isRecipient) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($report, 200);
    }

    /**
     * PATCH /aggregate-reports/{report}/send
     * body: { recipients: ['wakakesiswaan','kepalasekolah'], note?: string }
     * Hanya pemilik (Guru BK)
     */
  public function send(Request $req, $id)
{
    $req->validate([
        'recipients'   => 'required|array|min:1',
        'recipients.*' => 'in:wakakesiswaan,kepalasekolah',
        'note'         => 'nullable|string',
    ]);

    $report = AggregateReport::with('recipients')->findOrFail($id);
    if ($report->counselor_id !== Auth::id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $targets = User::whereIn('role', $req->recipients)->get(['id','name','role']);
    if ($targets->isEmpty()) {
        return response()->json(['message' => 'Penerima tidak ditemukan'], 422);
    }

    $sent = 0;
    foreach ($targets as $u) {
        AggregateReportRecipient::updateOrCreate(
            ['report_id' => $report->id, 'recipient_id' => $u->id],
            [
                'recipient_role' => $u->role,
                'sender_id'      => Auth::id(),  // ⬅️ kini ada kolomnya
                'note'           => $req->input('note'),
                'sent_at'        => now(),
            ]
        );
        $sent++;
    }

    if (is_null($report->sent_at)) { // ⬅️ kini kolom ada
        $report->update(['sent_at' => now()]);
    }

    return response()->json(['message' => "Terkirim ke {$sent} penerima"], 200);
}


    /** DELETE /aggregate-reports/{report} (owner) */
    public function destroy($id)
    {
        $report = AggregateReport::findOrFail($id);
        if ($report->counselor_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $report->delete();
        return response()->json(['message' => 'Report dihapus'], 200);
    }
    public function showShare($shareId)
{
    $uid = Auth::id();

    $share = AggregateReportRecipient::with([
        'report' => fn($q) => $q->with(['counselor:id,name','recipients'])
    ])->findOrFail($shareId);

    if ($share->recipient_id !== $uid) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return response()->json($share, 200);
}

public function ackShare($shareId)
{
    $uid = Auth::id();

    $share = AggregateReportRecipient::findOrFail($shareId);
    if ($share->recipient_id !== $uid) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    if (is_null($share->read_at)) {
        $share->update(['read_at' => now()]);
    }

    return response()->json(['message' => 'Ditandai sudah dibaca'], 200);
}

}
