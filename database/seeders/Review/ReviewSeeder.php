<?php

declare(strict_types=1);

namespace Database\Seeders\Review;

use App\Domains\Question\Models\Question;
use App\Domains\Review\Enums\Rating;
use App\Domains\Review\Models\ReviewLog;
use App\Domains\Review\Models\ReviewState;
use Database\Seeders\Question\QuestionSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Illuminate\Database\Seeder;

/**
 * Backdated scheduling state for the demo user, spread deliberately unevenly.
 *
 * Seeded uniformly, every question would come due on the same day and nothing about
 * scheduling would be observable. The spread below runs from questions several days
 * overdue to questions not due again for the better part of a year, so the due queue has
 * a real shape the first time anything looks at it.
 */
class ReviewSeeder extends Seeder
{
    /**
     * The state to give each reviewed question, as stability in days paired with how
     * many days from now it falls due. A negative offset is a question already overdue.
     *
     * @var array<int, array{float, int}>
     */
    private const SCHEDULE = [
        [0.5, -12],
        [0.8, -9],
        [1.5, -6],
        [2.5, -4],
        [3.0, -3],
        [4.0, -2],
        [4.5, -1],
        [5.0, 0],
        [6.0, 0],
        [7.5, 1],
        [9.0, 2],
        [12.0, 3],
        [15.0, 5],
        [18.0, 8],
        [21.0, 12],
        [26.0, 18],
        [32.0, 25],
        [40.0, 34],
        [50.0, 45],
        [65.0, 60],
        [80.0, 80],
        [100.0, 110],
        [130.0, 150],
        [170.0, 200],
    ];

    /**
     * Seed the review states and the log entries behind them.
     */
    public function run(): void
    {
        $topic = TopicSeeder::demoTopic();
        $questions = QuestionSeeder::demoQuestions()->take(count(self::SCHEDULE))->values();

        foreach ($questions as $index => $question) {
            [$stability, $dueInDays] = self::SCHEDULE[$index];

            $this->seedQuestion($topic->user_id, $topic->getKey(), $question, $stability, $dueInDays, $index);
        }
    }

    /**
     * Give one question its scheduling state, together with a log entry that accounts
     * for the state it ended up in.
     */
    private function seedQuestion(int $userId, int $topicId, Question $question, float $stability, int $dueInDays, int $index): void
    {
        $dueAt = now()->startOfHour()->addDays($dueInDays);
        $lastReviewedAt = $dueAt->subSeconds((int) round($stability * 86_400));

        $difficulty = round(3.0 + (($index % 7) * 1.05), 2);
        $reps = ($index % 9) + 1;
        $lapses = intdiv($index, 8);

        ReviewState::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'question_id' => $question->getKey(),
            ],
            [
                'topic_id' => $topicId,
                'stability' => $stability,
                'difficulty' => $difficulty,
                'due_at' => $dueAt,
                'last_reviewed_at' => $lastReviewedAt,
                'reps' => $reps,
                'lapses' => $lapses,
            ],
        );

        $alreadyLogged = ReviewLog::query()
            ->where('user_id', $userId)
            ->where('question_id', $question->getKey())
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        $isFirstReview = $reps === 1;

        ReviewLog::query()->create([
            'user_id' => $userId,
            'question_id' => $question->getKey(),
            'rating' => $isFirstReview ? Rating::Good : $this->ratingFor($lapses),
            'stability_before' => $isFirstReview ? null : round($stability / 1.8, 2),
            'difficulty_before' => $isFirstReview ? null : round($difficulty + 0.2, 2),
            'stability_after' => $stability,
            'difficulty_after' => $difficulty,
            'elapsed_days' => $isFirstReview ? null : round($stability / 2.1, 2),
            'scheduled_days' => $stability,
            'answered_in_milliseconds' => 4_200 + ($index * 310),
            'reviewed_at' => $lastReviewedAt,
        ]);
    }

    /**
     * The grade the last review was given. A question that has been forgotten before is
     * graded harder than one that never has, so the log is not uniformly optimistic.
     */
    private function ratingFor(int $lapses): Rating
    {
        return $lapses > 0 ? Rating::Hard : Rating::Good;
    }
}
