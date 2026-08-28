<?php

declare(strict_types=1);

namespace App\Domains\Generation\Actions;

use App\Domains\Generation\Data\GeneratedOutlineSectionData;
use App\Domains\Generation\Data\GeneratedQuestionData;
use App\Domains\Generation\Data\GeneratedQuizData;
use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Enums\QuestionType;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Enums\QuizStatus;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turn one validated generation run into the rows a person can study from.
 *
 * Everything here happens inside a single transaction: a half-written quiz is
 * worse than no quiz, so any throw leaves the source exactly as it was found and
 * the job records why. The source is only moved to `ready` once the whole set is
 * on disk, which makes `ready` mean what it says.
 *
 * The proposal is written even though nobody chose it. `quizzes.quiz_proposal_id`
 * is not nullable, because the product's real flow offers three outlines and
 * expands the accepted one; this change asks for one outline and accepts it on
 * the person's behalf. Writing it keeps an honest record of what the model
 * proposed and leaves the three-outline change nothing to migrate.
 */
class PersistGeneratedQuiz
{
    /**
     * Store the run and hand back the quiz it produced.
     */
    public function __invoke(Source $source, GeneratedQuizData $generated): Quiz
    {
        return DB::transaction(function () use ($source, $generated): Quiz {
            $topic = $this->resolveTopic($source, $generated->topicName);
            $proposal = $this->writeAcceptedProposal($source, $generated);
            $quiz = $this->writeQuiz($source, $topic, $proposal, $generated);

            $this->writeQuestions($quiz, $generated->questions);

            $this->markSourceReady($source, $topic, $generated->pageCount);

            return $quiz;
        });
    }

    /**
     * Find the person's topic of that name, or start one for them.
     *
     * Topics are matched on their slug rather than their name, so a second
     * document the model calls "the roman republic" joins the one it called
     * "The Roman Republic" instead of standing beside it. `topics` carries a
     * unique index on (user_id, slug), so the slug is both the natural key and
     * the constraint that keeps the two in step.
     */
    private function resolveTopic(Source $source, string $topicName): Topic
    {
        $name = trim($topicName);

        return Topic::query()->firstOrCreate(
            ['user_id' => $source->user_id, 'slug' => $this->slugFor($name)],
            ['name' => $name],
        );
    }

    /**
     * Derive the slug a topic is matched on.
     *
     * A name written in a script `Str::slug` cannot transliterate — Greek,
     * Cyrillic, Chinese — slugs to an empty string, which would file every such
     * topic of a person under one key. The lowercased name is used instead: less
     * tidy, but still one key per subject.
     */
    private function slugFor(string $name): string
    {
        $slug = Str::slug($name);

        return Str::limit($slug === '' ? Str::lower($name) : $slug, 255, '');
    }

    /**
     * Write the outline the model returned as the source's accepted proposal.
     */
    private function writeAcceptedProposal(Source $source, GeneratedQuizData $generated): QuizProposal
    {
        return QuizProposal::query()->create([
            'source_id' => $source->id,
            'position' => 0,
            'title' => $generated->title,
            'angle' => (string) __('Full coverage of the document'),
            'summary' => $this->summarise($generated),
            'suggested_topic_name' => $generated->topicName,
            'suggested_question_count' => count($generated->questions),
            'outline' => array_map(
                fn (GeneratedOutlineSectionData $section): array => [
                    'title' => $section->title,
                    'focus' => $section->focus,
                ],
                array_values($generated->outline),
            ),
            'language' => $generated->language,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Describe the run in one line, from the sections the model divided the
     * document into.
     */
    private function summarise(GeneratedQuizData $generated): string
    {
        $titles = array_map(
            fn (GeneratedOutlineSectionData $section): string => $section->title,
            array_values($generated->outline),
        );

        return $titles === [] ? $generated->title : implode(', ', $titles);
    }

    /**
     * Write the quiz the accepted proposal expands into.
     */
    private function writeQuiz(
        Source $source,
        Topic $topic,
        QuizProposal $proposal,
        GeneratedQuizData $generated,
    ): Quiz {
        return Quiz::query()->create([
            'user_id' => $source->user_id,
            'topic_id' => $topic->id,
            'source_id' => $source->id,
            'quiz_proposal_id' => $proposal->id,
            'title' => $generated->title,
            'status' => QuizStatus::Ready,
            'failure_reason' => null,
            'generated_at' => now(),
        ]);
    }

    /**
     * Write the questions and their options, keeping the order the model gave
     * them in.
     *
     * @param  array<int, GeneratedQuestionData>  $questions
     */
    private function writeQuestions(Quiz $quiz, array $questions): void
    {
        foreach (array_values($questions) as $position => $question) {
            $record = Question::query()->create([
                'quiz_id' => $quiz->id,
                'type' => QuestionType::SingleChoice,
                'text' => $question->text,
                'explanation' => $question->explanation,
                'position' => $position,
            ]);

            foreach (array_values($question->options) as $optionPosition => $option) {
                Option::query()->create([
                    'question_id' => $record->id,
                    'text' => $option->text,
                    'is_correct' => $option->isCorrect,
                    'position' => $optionPosition,
                ]);
            }
        }
    }

    /**
     * Move the source to `ready`, now that there is something to be ready for.
     *
     * The page count is recorded here rather than at upload because nothing in
     * the application can count the pages of a document; the model reports it and
     * the job has already refused anything above the limit.
     */
    private function markSourceReady(Source $source, Topic $topic, int $pageCount): void
    {
        $source->update([
            'topic_id' => $topic->id,
            'page_count' => $pageCount,
            'status' => SourceStatus::Ready,
            'failure_reason' => null,
            'analysed_at' => now(),
        ]);
    }
}
