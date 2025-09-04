<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class RiasecApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Dummy: 60 pertanyaan
        $dummyQuestions = [];
        for ($i = 1; $i <= 60; $i++) {
            $dummyQuestions[] = [
                'id'       => $i,
                'question' => "Question {$i}",
                'category' => 'Category',
            ];
        }

        // Fake O*NET endpoints
        Http::fake([
            '*questions*' => Http::response($dummyQuestions, 200),
            '*results*'   => Http::response([
                'Realistic'     => 10,
                'Investigative' => 20,
                'Artistic'      => 30,
                'Social'        => 40,
                'Enterprising'  => 50,
                'Conventional'  => 60,
            ], 200),
            '*job_zones*' => Http::response([
                ['code' => '1', 'title' => 'Zone 1'],
                ['code' => '2', 'title' => 'Zone 2'],
                ['code' => '3', 'title' => 'Zone 3'],
                ['code' => '4', 'title' => 'Zone 4'],
                ['code' => '5', 'title' => 'Zone 5'],
            ], 200),
            '*careers*'   => Http::response([
                ['code' => 'C1', 'title' => 'Career 1', 'matchScore' => 75],
                ['code' => 'C2', 'title' => 'Career 2', 'matchScore' => 60],
            ], 200),
        ]);
    }

    public function testFetchSubsetOfQuestions()
    {
        $response = $this->getJson('/api/riasec/questions?start=1&end=5');
        $response->assertStatus(200)
                 ->assertJsonCount(5)
                 ->assertJsonStructure([['id', 'question', 'category']]);
    }

    public function testFetchAllQuestionsByDefault()
    {
        $response = $this->getJson('/api/riasec/questions');
        $response->assertStatus(200)
                 ->assertJsonCount(60);
    }

    public function testResultsRequiresAnswers()
    {
        $this->getJson('/api/riasec/results')
             ->assertStatus(422);
    }

    public function testResultsReturnsScores()
    {
        $answers = implode(',', array_fill(0, 60, 1));
        $response = $this->getJson("/api/riasec/results?answers={$answers}");
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'Realistic',
                     'Investigative',
                     'Artistic',
                     'Social',
                     'Enterprising',
                     'Conventional',
                 ]);
    }

    public function testFetchJobZones()
    {
        $response = $this->getJson('/api/riasec/job-zones');
        $response->assertStatus(200)
                 ->assertJsonCount(5)
                 ->assertJsonStructure([['code', 'title']]);
    }

    public function testCareersRequiresAnswers()
    {
        $this->getJson('/api/riasec/careers')
             ->assertStatus(422);
    }

    public function testCareersReturnsList()
    {
        $answers = implode(',', array_fill(0, 60, 1));
        $response = $this->getJson("/api/riasec/careers?answers={$answers}");
        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonStructure([['code', 'title', 'matchScore']]);
    }
}
