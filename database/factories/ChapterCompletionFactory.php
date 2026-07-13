<?php

namespace Database\Factories;

use App\Models\ChapterCompletion;
use App\Models\ProgramChapter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterCompletionFactory extends Factory
{
    protected $model = ChapterCompletion::class;

    public function definition(): array
    {
        return [
            'program_chapter_id' => ProgramChapter::factory(),
            'date_traitement' => now()->toDateString(),
            'completed_by' => User::factory(),
            'remarque' => $this->faker->sentence(),
        ];
    }
}
