<?php

namespace App\Services;

use App\Models\RiasecQuestion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ONetService
{
    private string $base;       // ex: https://services.onetcenter.org/ws
    private string $ipBase;     // ex: https://services.onetcenter.org/ws/mnm/interestprofiler
    private string $user;       // BasicAuth username
    private string $pass;       // BasicAuth password
    private string $client;     // O*NET client id (untuk parameter client=...)
    private int    $totalQuestions = 60; // default 60 (short form)

    public function __construct()
    {
        $this->base   = rtrim((string) config('onet.base_url', 'https://services.onetcenter.org/ws'), '/');
        $this->ipBase = $this->base.'/mnm/interestprofiler';

        // catatan: pastikan config/onet.php sesuai dengan .env
        $this->user   = (string) config('onet.user', config('onet.client')); // fallback bila key-nya berbeda
        $this->pass   = (string) config('onet.pass', config('onet.key'));
        $this->client = (string) config('onet.client', '');

        $this->initQuestions(); // akan set $this->totalQuestions dari DB jika tersedia
    }

    /** Seed pertanyaan ke DB bila kosong, lalu set $totalQuestions */
    private function initQuestions(): void
    {
        $count = RiasecQuestion::count();
        if ($count < 1) {
            $questions = $this->fetchAllQuestions();
            foreach ($questions as $q) {
                RiasecQuestion::updateOrCreate(
                    ['question_id' => $q['id']],
                    [
                        'question_text' => $q['text'],
                        'category'      => $q['category'],
                    ]
                );
            }
            $count = count($questions);
        }
        if ($count > 0) {
            $this->totalQuestions = $count; // biasanya 60 (short form)
        }
    }

    /** Ambil 60 pertanyaan dari O*NET (JSON) lalu mapping ke {id,text,category} */
    public function fetchAllQuestions(): array
    {
        $res = Http::withBasicAuth($this->user, $this->pass)
            ->acceptJson()
            ->get("{$this->ipBase}/questions", [
                'start'  => 1,
                'end'    => 60,
                'client' => $this->client,
                'fmt'    => 'json',
            ]);

        if (!$res->ok()) {
            throw new \RuntimeException("O*NET fetch questions error: HTTP {$res->status()}");
        }

        $json   = $res->json();
        $list   = $json['questions'] ?? $json['question'] ?? $json ?? [];
        $output = [];

        // Format umum: each item punya "index" (1..60), "text", "area"
        foreach ((array) $list as $row) {
            $idx  = (int) ($row['index'] ?? $row['id'] ?? 0);
            $txt  = trim((string) ($row['text'] ?? ''));
            $area = (string) ($row['area'] ?? '');
            if ($idx > 0 && $txt !== '') {
                $output[] = ['id' => $idx, 'text' => $txt, 'category' => $area];
            }
        }

        return $output;
    }

    /** Pertanyaan dari DB yang sudah disimpan */
    public function getQuestions(): array
    {
        return RiasecQuestion::orderBy('question_id')
            ->get(['question_id as id', 'question_text as question', 'category'])
            ->toArray();
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions > 0 ? $this->totalQuestions : 60;
    }

    /** Hitung skor RIASEC di O*NET (endpoint yang benar: /score) */
    public function getResults(string $answers): array
    {
        $clean = $this->normalizeAnswers($answers); // wajib 60 digit 1..5

        $res = Http::withBasicAuth($this->user, $this->pass)
            ->acceptJson()
            ->get("{$this->ipBase}/score", [
                'answers' => $clean,
                'client'  => $this->client,
                'fmt'     => 'json',
            ]);

        if ($res->status() === 422) {
            Log::warning('O*NET /score 422', ['body' => $res->body()]);
            throw new \RuntimeException('O*NET score 422: '.$res->body());
        }
        if (!$res->ok()) {
            throw new \RuntimeException("O*NET score error: HTTP {$res->status()}");
        }

        // Contoh: { scores: [ {area: "Realistic", score: ..}, ... ] }
        return (array) $res->json();
    }

    /** List Job Zones dari O*NET */
    public function getJobZones(): array
    {
        $res = Http::withBasicAuth($this->user, $this->pass)
            ->acceptJson()
            ->get("{$this->ipBase}/job_zones", [
                'client' => $this->client,
                'fmt'    => 'json',
            ]);

        if (!$res->ok()) {
            throw new \RuntimeException("O*NET job_zones error: HTTP {$res->status()}");
        }

        return (array) $res->json();
    }

    /**
     * Rekomendasi karier dari O*NET:
     * /mnm/interestprofiler/careers?answers=...&job_zone=&start=&end=&fmt=json
     */
    public function getMatchingCareers(string $answers, ?int $jobZone = null, int $start = 1, int $end = 25): array
    {
        $clean  = $this->normalizeAnswers($answers);
        $params = [
            'answers' => $clean,
            'client'  => $this->client,
            'fmt'     => 'json',
            'start'   => $start,
            'end'     => $end,
        ];
        if ($jobZone) {
            $params['job_zone'] = $jobZone; // 1..5
        }

        $res = Http::withBasicAuth($this->user, $this->pass)
            ->acceptJson()
            ->get("{$this->ipBase}/careers", $params);

        if ($res->status() === 422) {
            Log::warning('O*NET /careers 422', ['body' => $res->body()]);
            throw new \RuntimeException('O*NET careers 422: '.$res->body());
        }
        if (!$res->ok()) {
            throw new \RuntimeException("O*NET careers error: HTTP {$res->status()}");
        }

        // Biarkan controller melakukan normalisasi item → {title, onet_code}
        return (array) $res->json();
    }

    // ================= Helpers =================

    /**
     * Pastikan jawaban 1..5 sebanyak N (default 60).
     * - Hanya ambil digit 1..5
     * - Harus tepat N digit; jika kurang/lebih -> lempar exception
     */
    private function normalizeAnswers(string $answers): string
    {
        $n      = $this->getTotalQuestions();
        $digits = preg_replace('/[^1-5]/', '', trim($answers)) ?? '';
        if (strlen($digits) !== $n) {
            throw new \RuntimeException("Expected {$n} answers (1..5), got ".strlen($digits));
        }
        return $digits;
    }
}
