<?php

declare(strict_types=1);

namespace Database\Seeders\Quiz;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Quiz\Enums\QuizStatus;
use App\Domains\Quiz\Models\Quiz;
use Database\Seeders\Generation\QuizProposalSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * The quizzes the accepted proposals were expanded into, one per accepted proposal.
 */
class QuizSeeder extends Seeder
{
    /**
     * Seed one quiz per accepted proposal.
     */
    public function run(): void
    {
        self::demoQuizzes();
    }

    /**
     * The demo quizzes, created on first call and returned unchanged afterwards.
     *
     * @return Collection<int, Quiz>
     */
    public static function demoQuizzes(): Collection
    {
        $topic = TopicSeeder::demoTopic();

        return QuizProposalSeeder::demoProposals()
            ->map(fn (QuizProposal $proposal): Quiz => Quiz::query()->firstOrCreate(
                ['quiz_proposal_id' => $proposal->getKey()],
                [
                    'user_id' => $topic->user_id,
                    'topic_id' => $topic->getKey(),
                    'source_id' => $proposal->source_id,
                    'title' => $proposal->title,
                    'status' => QuizStatus::Ready,
                    'generated_at' => now()->subDays(28),
                ],
            ));
    }
}
