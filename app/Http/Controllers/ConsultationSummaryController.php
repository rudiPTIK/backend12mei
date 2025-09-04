<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ConsultationSummaryController extends Controller
{
    /**
     * GET /summary/daily?start=YYYY-MM-DD&end=YYYY-MM-DD
     * Ringkasan per-hari untuk guru BK login.
     */
    public function daily(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        $guruId = Auth::id();

        $rows = DB::table('consultation_reports as r')
            ->join('consultations as c', 'c.id', '=', 'r.consultation_id')
            ->where('c.counselor_id', $guruId)
            ->whereBetween('r.started_at', [$start, $end])
            ->selectRaw("
                DATE(r.started_at) as tanggal,
                COUNT(*) as sesi_count,
                COUNT(DISTINCT r.siswa_id) as siswa_count,
                SUM(r.duration_minutes) as total_menit
            ")
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        // opsional: metrics total
        $totals = [
            'sesi'   => $rows->sum('sesi_count'),
            'siswa'  => $rows->sum('siswa_count'), // note: aggregate distinct across days akan double-count
            'menit'  => $rows->sum('total_menit'),
            'range'  => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ];

        return response()->json([
            'period' => 'daily',
            'start'  => $start->toDateString(),
            'end'    => $end->toDateString(),
            'items'  => $rows,
            'totals' => $totals,
        ], 200);
    }

    /**
     * GET /summary/weekly?start=YYYY-MM-DD&end=YYYY-MM-DD
     * Ringkasan per-minggu ISO (Senin–Minggu) untuk guru BK login.
     */
    public function weekly(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        $guruId = Auth::id();

        // YEARWEEK(date, 1) => mode ISO (Senin start)
        $rows = DB::table('consultation_reports as r')
            ->join('consultations as c', 'c.id', '=', 'r.consultation_id')
            ->where('c.counselor_id', $guruId)
            ->whereBetween('r.started_at', [$start, $end])
            ->selectRaw("
                YEARWEEK(r.started_at, 1) as iso_week,
                MIN(DATE(r.started_at)) as mulai,
                MAX(DATE(r.started_at)) as selesai,
                COUNT(*) as sesi_count,
                COUNT(DISTINCT r.siswa_id) as siswa_count,
                SUM(r.duration_minutes) as total_menit
            ")
            ->groupBy('iso_week')
            ->orderBy('mulai', 'desc')
            ->get()
            ->map(function ($row) {
                // derive week label (e.g. 2025-W35)
                $monday = Carbon::parse($row->mulai)->startOfWeek(Carbon::MONDAY);
                $row->week_label = $monday->isoWeekYear . '-W' . str_pad($monday->isoWeek, 2, '0', STR_PAD_LEFT);
                return $row;
            });

        $totals = [
            'sesi'  => $rows->sum('sesi_count'),
            'siswa' => $rows->sum('siswa_count'),
            'menit' => $rows->sum('total_menit'),
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ];

        return response()->json([
            'period' => 'weekly',
            'start'  => $start->toDateString(),
            'end'    => $end->toDateString(),
            'items'  => $rows,
            'totals' => $totals,
        ], 200);
    }

    /**
     * POST /summary/share
     * Body: { "period":"daily|weekly", "start":"YYYY-MM-DD", "end":"YYYY-MM-DD", "to_waka":true, "to_kepsek":false, "note":"..." }
     * Kirim ringkasan (harian/mingguan) ke pimpinan via email (stub sederhana).
     */
    public function share(Request $request)
    {
        $request->validate([
            'period'    => 'required|in:daily,weekly',
            'start'     => 'required|date',
            'end'       => 'required|date|after_or_equal:start',
            'to_wakakesiswaan'   => 'boolean',
            'to_kepalasekolah' => 'boolean',
            'note'      => 'nullable|string',
        ]);

        // Ambil data ringkasan sesuai period
        $subReq = new Request([
            'start' => $request->start,
            'end'   => $request->end,
        ]);

        $summary = $request->period === 'daily'
            ? json_decode($this->daily($subReq)->getContent(), true)
            : json_decode($this->weekly($subReq)->getContent(), true);

        // Target penerima berdasarkan role
        $recipients = [];
        if ($request->boolean('to_wakakesiswaan')) {
            $recipients = array_merge($recipients, $this->emailsByRole('wakakesiswaan'));
        }
        if ($request->boolean('to_kepalasekolah')) {
            $recipients = array_merge($recipients, $this->emailsByRole('kepalasekolah'));
        }
        $recipients = array_unique($recipients);

        if (empty($recipients)) {
            return response()->json(['message' => 'Tidak ada penerima yang dipilih'], 422);
        }

        // Kirim email (stub sederhana: text email)
        try {
            foreach ($recipients as $to) {
                Mail::raw($this->composeText($summary, $request->note), function ($m) use ($to, $summary) {
                    $subject = strtoupper($summary['period']).' Report '
                        .$summary['start'].' s/d '.$summary['end'];
                    $m->to($to)->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal mengirim', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Ringkasan terkirim', 'sent_to' => $recipients], 200);
    }

    // ===== Helpers =====

    private function dateRange(Request $request): array
    {
        $start = $request->filled('start') ? Carbon::parse($request->start)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
        $end   = $request->filled('end')   ? Carbon::parse($request->end)->endOfDay()   : Carbon::now()->endOfDay();
        return [$start, $end];
    }

    private function emailsByRole(string $role): array
    {
        return DB::table('users')->where('role', $role)->pluck('email')->toArray();
    }

    private function composeText(array $summary, ?string $note): string
    {
        $hdr = strtoupper($summary['period'])." REPORT {$summary['start']} s/d {$summary['end']}";
        $tot = $summary['totals'] ?? [];
        $txt = "{$hdr}\n"
             . "Total sesi: ".($tot['sesi'] ?? 0)."\n"
             . "Total menit: ".($tot['menit'] ?? 0)."\n\n";
        $txt .= "Rincian:\n";
        foreach ($summary['items'] as $item) {
            if ($summary['period'] === 'daily') {
                $txt .= "- {$item['tanggal']}: {$item['sesi_count']} sesi, {$item['siswa_count']} siswa, {$item['total_menit']} menit\n";
            } else {
                $label = $item['week_label'] ?? ($item['mulai'].'-'.$item['selesai']);
                $txt .= "- {$label}: {$item['sesi_count']} sesi, {$item['siswa_count']} siswa, {$item['total_menit']} menit\n";
            }
        }
        if ($note) {
            $txt .= "\nCatatan konselor: {$note}\n";
        }
        return $txt;
    }
}
