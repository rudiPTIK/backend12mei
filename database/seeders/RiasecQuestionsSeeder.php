<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\ONetService;
use App\Models\RiasecQuestion;

class RiasecQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ONetService::class);
        $questions = $service->fetchAllQuestions();

        foreach ($questions as $q) {
            RiasecQuestion::updateOrCreate(
                ['question_id' => (int) ($q['id'] ?? $q['question_id'] ?? 0)],
                [
                    'question_text' => $q['text'] ?? $q['question'] ?? '',
                    'category'      => $q['category'] ?? '',
                ]
            );
        }

        $this->command->info('RiasecQuestionsSeeder: 60 soal telah dimasukkan.');
    }
}
