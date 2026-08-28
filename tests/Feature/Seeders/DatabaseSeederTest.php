<?php

declare(strict_types=1);

use App\Domains\Attempt\Models\Attempt;
use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Models\Option;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Review\Models\ReviewLog;
use App\Domains\Review\Models\ReviewState;
use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Database\Seeders\Attempt\AttemptSeeder;
use Database\Seeders\Generation\QuizProposalSeeder;
use Database\Seeders\Question\QuestionSeeder;
use Database\Seeders\Review\ReviewSeeder;
use Database\Seeders\Source\SourceSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Database\Seeders\User\UserSeeder;

it('does not create a second demo account when seeded twice', function (): void {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->where('email', UserSeeder::DEMO_EMAIL)->count())->toBe(1);
});

it('files both demo sources under the demo account and its topic', function (): void {
    $this->seed(SourceSeeder::class);

    $user = User::query()->where('email', UserSeeder::DEMO_EMAIL)->sole();
    $topic = Topic::query()->where('slug', TopicSeeder::DEMO_SLUG)->sole();
    $sources = Source::query()->get();

    expect($sources)->toHaveCount(2)
        ->and($topic->user_id)->toBe($user->getKey())
        ->and($sources->pluck('user_id')->unique()->all())->toBe([$user->getKey()])
        ->and($sources->pluck('topic_id')->unique()->all())->toBe([$topic->getKey()])
        ->and($sources->every(fn (Source $source): bool => $source->status === SourceStatus::Ready))->toBeTrue();
});

it('accepts exactly one proposal per source', function (): void {
    $this->seed(QuizProposalSeeder::class);

    $sources = Source::query()->get();

    expect($sources)->toHaveCount(2);

    foreach ($sources as $source) {
        $proposals = QuizProposal::query()->where('source_id', $source->getKey())->get();

        expect($proposals)->toHaveCount((int) config('generation.proposal_count'))
            ->and($proposals->whereNotNull('accepted_at'))->toHaveCount(1)
            ->and($proposals->pluck('position')->all())->toBe(range(0, $proposals->count() - 1));
    }
});

it('expands every accepted proposal into a believable question set', function (): void {
    $this->seed(QuestionSeeder::class);

    $quizzes = Quiz::query()->get();
    $questions = Question::query()->get();

    expect($quizzes)->toHaveCount(QuizProposal::query()->whereNotNull('accepted_at')->count())
        ->and($questions)->toHaveCount(80)
        ->and($questions->pluck('text')->unique())->toHaveCount($questions->count());

    foreach ($quizzes as $quiz) {
        expect($quiz->questions)->toHaveCount(40);
    }

    foreach ($questions as $question) {
        $options = Option::query()->where('question_id', $question->getKey())->get();

        expect($options)->toHaveCount(4)
            ->and($options->where('is_correct', true))->toHaveCount(1)
            ->and($options->pluck('text')->unique())->toHaveCount(4)
            ->and($question->explanation)->not->toBeEmpty();
    }
});

it('records every question the abandoned attempt served', function (): void {
    $this->seed(AttemptSeeder::class);

    $abandoned = Attempt::query()->whereNull('completed_at')->sole();
    $responses = $abandoned->responses;

    expect($responses)->toHaveCount($abandoned->question_count)
        ->and($abandoned->correct_count)->toBeNull()
        ->and($responses->pluck('option_id')->unique()->all())->toBe([null])
        ->and($responses->pluck('is_correct')->unique()->all())->toBe([null])
        ->and($responses->pluck('answered_at')->unique()->all())->toBe([null])
        ->and($responses->pluck('question_id')->unique())->toHaveCount($responses->count());
});

it('serves the completed attempt on a different date from the abandoned one', function (): void {
    $this->seed(AttemptSeeder::class);

    $completed = Attempt::query()->whereNotNull('completed_at')->sole();
    $abandoned = Attempt::query()->whereNull('completed_at')->sole();

    expect($completed->served_on->toDateString())->not->toBe($abandoned->served_on->toDateString())
        ->and($completed->correct_count)->toBeLessThanOrEqual($completed->question_count)
        ->and($completed->responses)->toHaveCount($completed->question_count)
        ->and($completed->responses->whereNull('option_id'))->toHaveCount(0);
});

it('spreads the seeded review states across many due dates', function (): void {
    $this->seed(ReviewSeeder::class);

    $states = ReviewState::query()->get();
    $dueDates = $states->map(fn (ReviewState $state): string => $state->due_at->toDateString())->unique();

    expect($states)->not->toBeEmpty()
        ->and($dueDates->count())->toBeGreaterThanOrEqual(4)
        ->and($states->filter(fn (ReviewState $state): bool => $state->due_at->isPast()))->not->toBeEmpty()
        ->and($states->filter(fn (ReviewState $state): bool => $state->due_at->isAfter(now()->addMonths(3))))->not->toBeEmpty()
        ->and(ReviewLog::query()->count())->toBe($states->count());
});

it('keeps every review state due exactly its stability after the last review', function (): void {
    $this->seed(ReviewSeeder::class);

    foreach (ReviewState::query()->get() as $state) {
        expect($state->due_at->diffInSeconds($state->last_reviewed_at, absolute: true))
            ->toEqualWithDelta($state->stability * 86_400, 1.0);
    }
});

it('seeds the whole database from empty in dependency order', function (): void {
    $this->seed();

    expect(User::query()->count())->toBe(1)
        ->and(Topic::query()->count())->toBe(1)
        ->and(Source::query()->count())->toBe(2)
        ->and(QuizProposal::query()->count())->toBe(6)
        ->and(Quiz::query()->count())->toBe(2)
        ->and(Question::query()->count())->toBe(80)
        ->and(Option::query()->count())->toBe(320)
        ->and(Attempt::query()->count())->toBe(2)
        ->and(ReviewState::query()->count())->toBe(24)
        ->and(ReviewLog::query()->count())->toBe(24);
});

it('leaves the seeded row counts unchanged when the whole database is seeded twice', function (): void {
    $this->seed();
    $this->seed();

    expect(User::query()->count())->toBe(1)
        ->and(Source::query()->count())->toBe(2)
        ->and(QuizProposal::query()->count())->toBe(6)
        ->and(Question::query()->count())->toBe(80)
        ->and(Attempt::query()->count())->toBe(2)
        ->and(ReviewState::query()->count())->toBe(24);
});
