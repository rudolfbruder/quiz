<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Attempt\AttemptSeeder;
use Database\Seeders\Generation\QuizProposalSeeder;
use Database\Seeders\Question\QuestionSeeder;
use Database\Seeders\Quiz\QuizSeeder;
use Database\Seeders\Review\ReviewSeeder;
use Database\Seeders\Source\SourceSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Database\Seeders\User\UserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The order is the order the foreign keys require: an account, then the topic it
     * studies, the documents filed under it, the proposals analysis produced, the quiz
     * accepted from one of them, its questions, the attempts they were served in, and
     * finally the scheduling state those attempts left behind.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TopicSeeder::class,
            SourceSeeder::class,
            QuizProposalSeeder::class,
            QuizSeeder::class,
            QuestionSeeder::class,
            AttemptSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
