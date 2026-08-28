<?php

declare(strict_types=1);

use App\Domains\Generation\Actions\PersistGeneratedQuiz;
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
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * Nothing here reaches a provider: the action is handed a hydrated run, which is
 * all it ever sees of one.
 *
 * @param  array<string, mixed>  $overrides
 */
function persistGeneratedQuiz(Source $source, array $overrides = []): Quiz
{
    return app(PersistGeneratedQuiz::class)(
        $source,
        GeneratedQuizData::hydrate(generationRunPayload($overrides)),
    );
}

it('writes the whole quiz and leaves the source ready with its page count', function (): void {
    $source = Source::factory()->create();

    $quiz = persistGeneratedQuiz($source);

    $topic = Topic::query()->sole();
    $proposal = QuizProposal::query()->sole();

    expect($topic->user_id)->toBe($source->user_id)
        ->and($topic->name)->toBe('The Roman Republic')
        ->and($topic->slug)->toBe('the-roman-republic');

    expect($proposal->source_id)->toBe($source->id)
        ->and($proposal->position)->toBe(0)
        ->and($proposal->accepted_at)->not->toBeNull()
        ->and($proposal->suggested_topic_name)->toBe('The Roman Republic')
        ->and($proposal->suggested_question_count)->toBe(3)
        ->and($proposal->language)->toBe('English')
        ->and($proposal->outline)->toBe([
            ['title' => 'The Gracchi', 'focus' => 'Land reform and the first political violence'],
            ['title' => 'Sulla', 'focus' => 'The dictatorship and the precedent it set'],
        ]);

    expect($quiz->user_id)->toBe($source->user_id)
        ->and($quiz->source_id)->toBe($source->id)
        ->and($quiz->topic_id)->toBe($topic->id)
        ->and($quiz->quiz_proposal_id)->toBe($proposal->id)
        ->and($quiz->title)->toBe('The fall of the Roman Republic')
        ->and($quiz->status)->toBe(QuizStatus::Ready)
        ->and($quiz->generated_at)->not->toBeNull();

    $questions = $quiz->questions()->get();

    expect($questions)->toHaveCount(3)
        ->and($questions->pluck('position')->all())->toBe([0, 1, 2])
        ->and($questions->first()->type)->toBe(QuestionType::SingleChoice)
        ->and($questions->first()->text)->toBe('Which office did Tiberius Gracchus hold in 133 BC?');

    $options = $questions->first()->options()->get();

    expect($options)->toHaveCount(4)
        ->and($options->pluck('position')->all())->toBe([0, 1, 2, 3])
        ->and($options->where('is_correct', true)->pluck('text')->all())->toBe(['Tribune of the plebs']);

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Ready)
        ->and($source->page_count)->toBe(12)
        ->and($source->topic_id)->toBe($topic->id)
        ->and($source->failure_reason)->toBeNull()
        ->and($source->analysed_at)->not->toBeNull();
});

it('files a second document under the topic the person already has', function (): void {
    $user = User::factory()->create();
    $topic = Topic::factory()->for($user)->create(['name' => 'The Roman Republic', 'slug' => 'the-roman-republic']);

    persistGeneratedQuiz(Source::factory()->for($user)->create());
    persistGeneratedQuiz(
        Source::factory()->for($user)->create(),
        ['topic_name' => 'the roman republic', 'title' => 'The Republic in crisis'],
    );

    expect(Topic::query()->count())->toBe(1)
        ->and(Quiz::query()->where('topic_id', $topic->id)->count())->toBe(2)
        ->and($topic->refresh()->name)->toBe('The Roman Republic');
});

it('leaves another person topic of the same name untouched', function (): void {
    $hers = Topic::factory()->create(['name' => 'The Roman Republic', 'slug' => 'the-roman-republic']);

    $his = User::factory()->create();
    $quiz = persistGeneratedQuiz(Source::factory()->for($his)->create());

    expect(Topic::query()->count())->toBe(2)
        ->and($quiz->topic_id)->not->toBe($hers->id)
        ->and($quiz->topic->user_id)->toBe($his->id)
        ->and($hers->refresh()->quizzes()->count())->toBe(0);
});

it('writes nothing at all when the run fails partway through', function (): void {
    $source = Source::factory()->create();

    // The topic, the proposal, the quiz and the first question are already
    // written by the time the options are reached, so a failure here proves the
    // transaction rather than the order of the writes.
    DB::listen(function (QueryExecuted $query): void {
        if (str_contains($query->sql, 'insert into "options"')) {
            throw new RuntimeException('The database went away.');
        }
    });

    expect(fn (): Quiz => persistGeneratedQuiz($source))->toThrow(RuntimeException::class);

    expect(Quiz::query()->count())->toBe(0)
        ->and(Question::query()->count())->toBe(0)
        ->and(Option::query()->count())->toBe(0)
        ->and(QuizProposal::query()->count())->toBe(0)
        ->and(Topic::query()->count())->toBe(0);

    $source->refresh();

    expect($source->status)->toBe(SourceStatus::Pending)
        ->and($source->page_count)->toBeNull()
        ->and($source->topic_id)->toBeNull();
});
