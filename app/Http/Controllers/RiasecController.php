<?php
namespace App\Http\Controllers;

use App\Models\RiasecQuestion;
use App\Models\RiasecResponse;
use App\Models\RiasecCareer;
use App\Models\RiasecResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
//testinggggg
class RiasecController extends Controller
{
    // Ambil semua pertanyaan
    public function questions()
    {
        // logging debug
        Log::info('RiasecController@questions dipanggil; total='.RiasecQuestion::count());

        $qs = RiasecQuestion::select('id','text','riasec_type')->get();
        return response()->json($qs->toArray());
    }

    // Simpan jawaban, hitung skor, simpan history, dan rekomendasi
    public function storeResponses(Request $request)
    {
        $data = $request->validate([
            'responses'               => 'required|array',
            'responses.*.question_id' => 'required|exists:riasec_questions,id',
            'responses.*.score'       => 'required|integer|min:1|max:5',
        ]);

        $userId = $request->user()->id;
        Log::info("RiasecController@storeResponses menerima ".count($data['responses'])." jawaban dari user {$userId}");

        // Hapus jawaban & history lama
        RiasecResponse::where('user_id',$userId)->delete();
        RiasecResult::where('user_id',$userId)->delete();

        // Simpan jawaban baru
        foreach ($data['responses'] as $r) {
            RiasecResponse::create([
                'user_id'     => $userId,
                'question_id' => $r['question_id'],
                'score'       => $r['score'],
            ]);
        }

        // Hitung skor mentah
        $raw = DB::table('riasec_responses')
            ->join('riasec_questions','riasec_responses.question_id','=','riasec_questions.id')
            ->where('riasec_responses.user_id',$userId)
            ->groupBy('riasec_questions.riasec_type')
            ->select('riasec_questions.riasec_type', DB::raw('SUM(score) as total'))
            ->pluck('total','riasec_type');

        // Normalisasi ke persentase
        $percent = [];
        foreach (['R','I','A','S','E','C'] as $t) {
            $count  = RiasecQuestion::where('riasec_type',$t)->count();
            $max    = $count * 5;
            $rawVal = $raw->get($t,0);
            $percent[$t] = $max ? round($rawVal/$max*100,1) : 0;
        }

        // Top 2 domains
        arsort($percent);
        $top2 = array_slice(array_keys($percent), 0, 2);

        // Simpan history
        RiasecResult::create([
            'user_id'    => $userId,
            'score_R'    => $raw->get('R',0),
            'score_I'    => $raw->get('I',0),
            'score_A'    => $raw->get('A',0),
            'score_S'    => $raw->get('S',0),
            'score_E'    => $raw->get('E',0),
            'score_C'    => $raw->get('C',0),
            'top_domains'=> implode(',',$top2),
        ]);

        // Ambil rekomendasi careers
        $recs = RiasecCareer::whereIn('riasec_type',$top2)
                             ->get(['name','description','riasec_type']);
    
        // logging hasil
        Log::info('Raw scores: '.json_encode($raw->toArray()));
        Log::info('Top domains: '.implode(',',$top2));

        return response()->json([
            'raw_scores'      => $raw->toArray(),
            'percentages'     => $percent,
            'top_domains'     => $top2,
            'recommendations' => $recs->toArray(),
        ]);
    }

    // Ambil hasil & rekomendasi terakhir
    public function results(Request $request)
    {
        $userId = $request->user()->id;
        $history = RiasecResult::where('user_id',$userId)->latest()->first();

        if (!$history) {
            return response()->json(['message'=>'Belum ada hasil RIASEC'], 404);
        }

        $top2 = explode(',',$history->top_domains);
        $careers = RiasecCareer::whereIn('riasec_type',$top2)
                    ->get(['name','description','riasec_type']);

        return response()->json([
            'scores'          => [
                'R'=>$history->score_R,
                'I'=>$history->score_I,
                'A'=>$history->score_A,
                'S'=>$history->score_S,
                'E'=>$history->score_E,
                'C'=>$history->score_C,
            ],
            'top_domains'     => $top2,
            'recommendations' => $careers->toArray(),
        ]);
    }
    public function history(Request $request)
    {
        $userId = $request->user()->id;

        $results = RiasecResult::where('user_id', $userId)
                     ->orderBy('created_at', 'desc')
                     ->get();

        $data = $results->map(function ($h) {
            $top2 = explode(',', $h->top_domains);
            $careers = RiasecCareer::whereIn('riasec_type', $top2)
                        ->get(['name','description','riasec_type']);

            // Hitung persentase ulang (atau bisa simpan di kolom terpisah)
            $percent = [];
            foreach (['R','I','A','S','E','C'] as $t) {
                $count = DB::table('riasec_questions')->where('riasec_type',$t)->count();
                $max   = $count * 5;
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
    public function guruResults(Request $request)
    {
        $results = RiasecResult::with('user:id,name')
            ->whereHas('user', fn($q) => $q->where('role', 'siswa'))
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $results->map(function ($h) {
            // Top 2 domains
            $top2 = explode(',', $h->top_domains);

            // Hitung persentase per domain
            $percentages = [];
            foreach (['R','I','A','S','E','C'] as $type) {
                $count = RiasecQuestion::where('riasec_type', $type)->count();
                $max   = $count * 5;
                $raw   = $h->{'score_' . $type};
                $percentages[$type] = $max ? round($raw / $max * 100, 1) : 0;
            }

            // Rekomendasi careers berdasarkan top domains
            $careers = RiasecCareer::whereIn('riasec_type', $top2)
                        ->get(['name', 'description', 'riasec_type']);

            return [
                'id'              => $h->id,
                'student'         => ['id' => $h->user->id, 'name' => $h->user->name],
                'raw_scores'      => [
                    'R' => $h->score_R, 'I' => $h->score_I,
                    'A' => $h->score_A, 'S' => $h->score_S,
                    'E' => $h->score_E, 'C' => $h->score_C,
                ],
                'percentages'     => $percentages,
                'top_domains'     => $top2,
                'recommendations' => $careers->toArray(),
                'tested_at'       => $h->created_at->toDateString(),
            ];
        });

        return response()->json($data);
    }
}