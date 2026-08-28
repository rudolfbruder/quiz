<?php

declare(strict_types=1);

namespace Database\Seeders\Attempt;

use App\Domains\Attempt\Enums\AttemptKind;
use App\Domains\Attempt\Models\Attempt;
use App\Domains\Attempt\Models\Response;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Topic\Models\Topic;
use Database\Seeders\Question\QuestionSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Two daily quizzes already served to the demo user: yesterday's, worked through to the
 * end, and today's, served and then abandoned.
 *
 * The abandoned one is the interesting fixture. Its questions were recorded the moment
 * they were served, so they still count as shown even though nothing was answered, and
 * anything that selects questions has to reckon with them.
 */
class AttemptSeeder extends Seeder
{
    /**
     * How many of the served questions of the completed attempt were answered correctly.
     */
    private const CORRECT_ANSWERS = 3;

    /**
     * Seed the completed and the abandoned attempt.
     */
    public function run(): void
    {
        $topic = TopicSeeder::demoTopic();
        $size = (int) config('review.daily_question_count');

        $questions = QuestionSeeder::demoQuestions()->take($size * 2);

        $this->seedCompleted($topic, $questions->take($size));
        $this->seedAbandoned($topic, $questions->skip($size)->take($size));
    }

    /**
     * Yesterday's quiz, answered all the way through and scored.
     *
     * @param  Collection<int, Question>  $questions
     */
    private function seedCompleted(Topic $topic, Collection $questions): void
    {
        $servedOn = today()->subDay();

        $attempt = Attempt::query()->firstOrCreate(
            [
                'user_id' => $topic->user_id,
                'topic_id' => $topic->getKey(),
                'served_on' => $servedOn,
            ],
            [
                'kind' => AttemptKind::Daily,
                'question_count' => $questions->count(),
                'correct_count' => min(self::CORRECT_ANSWERS, $questions->count()),
                'started_at' => $servedOn->copy()->setTime(8, 12),
                'completed_at' => $servedOn->copy()->setTime(8, 19),
            ],
        );

        if ($attempt->responses()->exists()) {
            return;
        }

        foreach ($questions->values() as $position => $question) {
            $answeredCorrectly = $position < self::CORRECT_ANSWERS;

            Response::query()->create([
                'attempt_id' => $attempt->getKey(),
                'question_id' => $question->getKey(),
                'option_id' => $this->optionFor($question, $answeredCorrectly)->getKey(),
                'position' => $position,
                'is_correct' => $answeredCorrectly,
                'answered_in_milliseconds' => 6_400 + ($position * 2_150),
                'served_at' => $attempt->started_at,
                'answered_at' => $attempt->started_at->addSeconds(45 + ($position * 70)),
            ]);
        }
    }

    /**
     * Today's quiz, served and then left. Every question is recorded, none is answered.
     *
     * @param  Collection<int, Question>  $questions
     */
    private function seedAbandoned(Topic $topic, Collection $questions): void
    {
        $servedOn = today();

        $attempt = Attempt::query()->firstOrCreate(
            [
                'user_id' => $topic->user_id,
                'topic_id' => $topic->getKey(),
                'served_on' => $servedOn,
            ],
            [
                'kind' => AttemptKind::Daily,
                'question_count' => $questions->count(),
                'correct_count' => null,
                'started_at' => $servedOn->copy()->setTime(7, 55),
                'completed_at' => null,
            ],
        );

        if ($attempt->responses()->exists()) {
            return;
        }

        foreach ($questions->values() as $position => $question) {
            Response::query()->create([
                'attempt_id' => $attempt->getKey(),
                'question_id' => $question->getKey(),
                'option_id' => null,
                'position' => $position,
                'is_correct' => null,
                'answered_in_milliseconds' => null,
                'served_at' => $attempt->started_at,
                'answered_at' => null,
            ]);
        }
    }

    /**
     * An option of the given question: the correct one, or the first that is not.
     */
    private function optionFor(Question $question, bool $correct): Option
    {
        return $question->options()
            ->where('is_correct', $correct)
            ->orderBy('position')
            ->firstOrFail();
    }
}
