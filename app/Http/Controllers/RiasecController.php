<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ONetService;
use App\Models\RiasecTest;
use Illuminate\Support\Facades\Auth;
class RiasecController extends Controller
{
    public function __construct(private ONetService $onet) {}

    /** GET /api/riasec/questions?start=&end= */
    public function questions(Request $request)
    {
        $start = max(1, min(60, (int) $request->query('start', 1)));
        $end   = max($start, min(60, (int) $request->query('end', 60)));

        $all  = $this->onet->getQuestions();
        $data = array_filter($all, fn ($q) => $q['id'] >= $start && $q['id'] <= $end);

        return response()->json(array_values($data));
    }
    private function storageToOnet(string $storage): string
    {
        $n = $this->onet->getTotalQuestions(); // biasanya 60
        // ambil hanya 0..5 dari storage (storage boleh mengandung '0' untuk kosong)
        $digits = preg_replace('/[^0-5]/', '', $storage) ?? '';
        $digits = substr($digits, 0, $n);
        // pastikan panjang n dengan padding '0', lalu ganti semua '0' menjadi '3' (netral)
        $digits = str_pad($digits, $n, '0');
        return strtr($digits, ['0' => '3']);
    }
    /** GET /api/riasec/results?answers=... */
    public function results(Request $request)
    {
       $request->validate(['answers' => 'required|string|max:240']);

        // simpan versi storage (boleh ada '0')
        $answersStorage = $this->normalizeForStorage((string) $request->input('answers'));

        // KIRIM ke O*NET pakai versi lengkap (0 → 3)
        $raw = null;
        try {
            $answersForOnet = $this->storageToOnet($answersStorage);
            $raw = $this->onet->getResults($answersForOnet);
        } catch (\Throwable $e) {
            logger()->warning('O*NET results error', ['msg' => $e->getMessage()]);
        }

        $scoresMap = $raw ? $this->toScoresMap($raw) : null;
        if (!$scoresMap || array_sum($scoresMap) === 0) {
            // fallback lokal dari storage string (0 dianggap 0)
            $scoresMap = $this->localScoresFromAnswers($answersStorage);
        }

        $code3 = $this->top3Code($scoresMap);

        $test = RiasecTest::create([
            'user_id'    => Auth::id(),
            'answers'    => $answersStorage,
            'scores'     => $scoresMap,
            'r'          => $scoresMap['R'] ?? 0,
            'i'          => $scoresMap['I'] ?? 0,
            'a'          => $scoresMap['A'] ?? 0,
            's'          => $scoresMap['S'] ?? 0,
            'e'          => $scoresMap['E'] ?? 0,
            'c'          => $scoresMap['C'] ?? 0,
            'code3'      => $code3,
            'client'     => config('onet.client'),
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $scoresList = (is_array($raw) && isset($raw['scores']) && is_array($raw['scores']))
            ? $raw['scores']
            : [];

        return response()->json([
            'test_id'    => $test->id,
            'scores'     => $scoresList,
            'scores_map' => $scoresMap,
            'code3'      => $code3,
            'message'    => 'RIASEC test saved',
        ], 201);
    }

    /** GET /api/riasec/job-zones */
    public function jobZones()
    {
        return response()->json($this->onet->getJobZones());
    }

    /**
     * GET /api/riasec/careers?answers=... | ?test_id=...
     * Selalu return array of {title, onet_code}
     */
   public function careers(Request $request)
{
    $request->validate([
        'answers' => 'nullable|string|max:240',
        'test_id' => 'nullable|integer',
    ]);

    $answers = $request->input('answers');
    $test    = null;

    if ($request->filled('test_id')) {
        $test = \App\Models\RiasecTest::find($request->integer('test_id'));
        if ($test) {
            $answers = $test->answers; // string storage (bisa mengandung '0')
        }
    }

    if (!$answers) {
        return response()->json(['error' => 'answers atau test_id wajib diisi'], 422);
    }

    // SELALU kirim 60 digit 1..5 ke O*NET (0 → 3)
    $list = [];
    try {
        $answersForOnet = $this->storageToOnet($answers);
        $careersRaw = $this->onet->getMatchingCareers($answersForOnet);
    } catch (\Throwable $e) {
        logger()->error('O*NET careers error', ['msg' => $e->getMessage()]);
        return response()->json([]); // graceful
    }

    // normalisasi → list {title, onet_code}
    $rows = $this->extractCareerList($careersRaw);
    foreach ($rows as $row) {
        $norm = $this->normalizeCareerItem($row);
        if ($norm) $list[] = $norm;
        if (count($list) >= 50) break;
    }

    // dedup + simpan kalau via test_id (top 20)
    $seen = [];
    $list = array_values(array_filter($list, function ($r) use (&$seen) {
        $key = strtolower(($r['title'] ?? '').'|'.($r['onet_code'] ?? ''));
        if (($r['title'] ?? '') === '') return false;
        if (isset($seen[$key])) return false;
        $seen[$key] = true;
        return true;
    }));
    if ($test) {
        $test->careers()->delete();
        foreach ($list as $i => $r) {
            $test->careers()->create([
                'title'     => $r['title'],
                'onet_code' => $r['onet_code'],
                'rank'      => $i + 1,
            ]);
            if ($i >= 19) break;
        }
    }

    return response()->json($list);
}


    /** GET /api/riasec/history */
    public function history(Request $request)
    {
        $q = RiasecTest::query()->withCount('careers')->latest();

        if ($request->user()) {
            $q->where('user_id', $request->user()->id);
        } else {
            $q->where('ip', $request->ip())
              ->where('user_agent', substr((string) $request->userAgent(), 0, 255));
        }

        $per = min(100, max(1, (int) $request->query('per_page', 20)));
        return response()->json($q->paginate($per));
    }

    /** GET /api/riasec/tests/{test} */
    public function show(Request $request, RiasecTest $test)
    {
        if ($request->user() && $test->user_id && $test->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $test->load('careers');
        return response()->json($test);
    }

    /** DELETE /api/riasec/tests/{test} */
    public function destroy(Request $request, RiasecTest $test)
    {
        if ($request->user() && $test->user_id && $test->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $test->careers()->delete();
        $test->delete();
        return response()->json(['deleted' => true]);
    }

    // ================= Helpers (controller) =================

    private function normalizeForStorage(string $answers): string
    {
        $trim = trim($answers);

        // dukung JSON [{id,answer}]
        if (str_starts_with($trim, '[')) {
            $arr = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                $map = [];
                foreach ($arr as $it) {
                    if (isset($it['id'], $it['answer'])) {
                        $map[(int) $it['id']] = (int) $it['answer'];
                    }
                }
                ksort($map);
                $trim = implode('', array_values($map));
            }
        }

        $expected = $this->onet->getTotalQuestions();
        $digits   = preg_replace('/[^1-5]/', '', $trim) ?? '';
        $digits   = substr($digits, 0, $expected);

        // untuk penyimpanan, yang kosong dipad '0' (JANGAN untuk kirim ke O*NET)
        return str_pad($digits, $expected, '0');
    }

    private function toScoresMap(array $raw): array
    {
        $keys = ['R','I','A','S','E','C'];
        $out  = array_fill_keys($keys, 0);

        if (isset($raw['scores']) && is_array($raw['scores'])) {
            foreach ($raw['scores'] as $it) {
                $area  = (string) ($it['area'] ?? '');
                $score = (int) ($it['score'] ?? 0);
                if ($area !== '') {
                    $k = strtoupper(substr($area, 0, 1));
                    if (isset($out[$k])) $out[$k] = $score;
                }
            }
        }
        foreach ($keys as $k) {
            if (isset($raw[$k]) && is_numeric($raw[$k])) {
                $out[$k] = (int) $raw[$k];
            }
        }
        return $out;
    }

    private function localScoresFromAnswers(string $answers): array
    {
        $n      = $this->onet->getTotalQuestions();
        $digits = array_map('intval', str_split(substr($answers, 0, $n)));
        $sum    = fn(array $slice) => array_sum($slice);

        return [
            'R' => $sum(array_slice($digits,  0, 10)),
            'I' => $sum(array_slice($digits, 10, 10)),
            'A' => $sum(array_slice($digits, 20, 10)),
            'S' => $sum(array_slice($digits, 30, 10)),
            'E' => $sum(array_slice($digits, 40, 10)),
            'C' => $sum(array_slice($digits, 50, 10)),
        ];
    }

    private function top3Code(array $scoresMap): string
    {
        $order = ['R','I','A','S','E','C'];
        uksort($scoresMap, function ($a, $b) use ($scoresMap, $order) {
            $diff = $scoresMap[$b] <=> $scoresMap[$a];
            return $diff ?: (array_search($a, $order, true) <=> array_search($b, $order, true));
        });
        return implode('', array_slice(array_keys($scoresMap), 0, 3));
    }

    /** Tarik list dari payload O*NET (careers/results/items/data/list) */
    private function extractCareerList(mixed $data): array
    {
        if (is_array($data)) {
            foreach (['careers','career','results','items','data','list'] as $k) {
                if (isset($data[$k]) && is_array($data[$k])) {
                    return array_values($data[$k]);
                }
            }
            if (array_is_list($data)) return $data;
            foreach ($data as $v) {
                $found = $this->extractCareerList($v);
                if (!empty($found)) return $found;
            }
        }
        return [];
    }

    /** Normalisasi ke {title, onet_code}; abaikan string angka panjang */
    private function normalizeCareerItem(mixed $item): ?array
    {
        if (is_string($item)) {
            $t = trim($item);
            if ($t === '' || preg_match('/^\d+$/', $t)) return null;
            return ['title' => $t, 'onet_code' => null];
        }
        if (is_array($item)) {
            $title = $item['title']
                ?? $item['occupationTitle']
                ?? $item['jobTitle']
                ?? $item['name']
                ?? ($item[0] ?? null);

            $code  = $item['code']
                ?? $item['occupationCode']
                ?? $item['soc_code']
                ?? $item['onet_soc_code']
                ?? null;

            $title = is_string($title) ? trim($title) : '';
            if ($title === '' || preg_match('/^\d+$/', $title)) return null;

            return [
                'title'     => $title,
                'onet_code' => is_string($code) ? trim($code) : null,
            ];
        }
        return null;
    }
}
